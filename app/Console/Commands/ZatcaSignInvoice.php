<?php

namespace App\Console\Commands;

use App\Services\QrCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage as LaravelStorage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Saleh7\Zatca\Helpers\Certificate;
use Saleh7\Zatca\InvoiceSigner;
use Saleh7\Zatca\Storage as ZatcaStorage;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ZatcaSignInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zatca:sign-invoice {invoiceFile=Simplified_Invoice.xml : The invoice XML file to sign}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sign ZATCA invoice XML with the compliance certificate';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $invoiceFileName = $this->argument('invoiceFile');
            $disk = LaravelStorage::disk('local');
            $directory = 'zatca/output';
            $disk->makeDirectory($directory);

            $invoiceRelativePath = $directory.'/'.$invoiceFileName;
            if (! $disk->exists($invoiceRelativePath)) {
                $this->error("Invoice file not found: {$invoiceFileName}");

                return SymfonyCommand::FAILURE;
            }

            $certificateJsonPath = $directory.'/ZATCA_certificate_data.json';
            if (! $disk->exists($certificateJsonPath)) {
                $this->error('Certificate data not found. Request a compliance certificate first.');

                return SymfonyCommand::FAILURE;
            }

            $zatcaStorage = new ZatcaStorage();
            $invoiceAbsolutePath = $disk->path($invoiceRelativePath);
            $certificateAbsolutePath = $disk->path($certificateJsonPath);

            $this->info("Loading unsigned invoice: {$invoiceFileName}");
            $xmlInvoice = $zatcaStorage->get($invoiceAbsolutePath);

            $jsonCertificate = $zatcaStorage->get($certificateAbsolutePath);
            $jsonData = json_decode($jsonCertificate, true, 512, JSON_THROW_ON_ERROR);

            $certificateContent = $jsonData['certificate'] ?? null;
            $secret = $jsonData['secret'] ?? null;

            if (! $certificateContent || ! $secret) {
                $this->error('Invalid certificate data file.');

                return SymfonyCommand::FAILURE;
            }

            $this->info('Certificate loaded successfully.');

            $privateKeyPath = $disk->path($directory.'/private.pem');
            if (! $disk->exists($directory.'/private.pem')) {
                $this->error('Private key not found. Generate it via `php artisan zatca:generate-csr`.');

                return SymfonyCommand::FAILURE;
            }

            $privateKey = file_get_contents($privateKeyPath);
            $cleanPrivateKey = trim(str_replace(
                ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n"],
                '',
                $privateKey
            ));

            $this->info('Private key loaded.');
            $certificate = new Certificate($certificateContent, $cleanPrivateKey, $secret);

            $this->info('Signing the invoice...');
            $signer = InvoiceSigner::signInvoice($xmlInvoice, $certificate);
            $signedInvoice = $signer->getXML();

            $signedFileName = str_replace('.xml', '_signed.xml', $invoiceFileName);
            $signedRelativePath = $directory.'/'.$signedFileName;
            $disk->put($signedRelativePath, $signedInvoice);

            // Generate and save QR code image
            $this->info('Generating QR code image...');
            $invoiceId = pathinfo($invoiceFileName, PATHINFO_FILENAME);
            
            // Get QR code base64 from signer
            // The base64 string is what should be encoded in the QR code
            $qrCodeBase64 = $signer->getQRCode();
            
            // Generate QR code image from base64 string
            $qrCodePath = $this->generateQrCodeImage($qrCodeBase64, $invoiceId, $disk, $directory);
            
            if ($qrCodePath) {
                $this->info('✓ QR code image saved to: '.$qrCodePath);
            } else {
                $this->warn('Could not generate QR code image.');
            }

            $this->info('Invoice signed successfully!');
            $this->info('Signed invoice saved to: '.$disk->path($signedRelativePath));

            return SymfonyCommand::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());
            $this->line('Debug: '.$e->getFile().':'.$e->getLine());

            return SymfonyCommand::FAILURE;
        }
    }

    /**
     * Generate QR code image from base64 encoded TLV string
     * Returns PNG if possible, otherwise SVG
     */
    private function generateQrCodeImage(string $qrCodeBase64, string $invoiceId, $disk, string $directory): ?string
    {
        try {
            $disk->makeDirectory('zatca/qr-codes');
            
            // Try to generate PNG first (requires imagick or proper GD configuration)
            $qrCodePath = "zatca/qr-codes/{$invoiceId}.png";
            $absolutePath = $disk->path($qrCodePath);
            
            try {
                // Generate PNG directly - use base64 string as QR code content
                QrCode::format('png')
                    ->size(300)
                    ->margin(2)
                    ->errorCorrection('H')
                    ->encoding('UTF-8')
                    ->generate($qrCodeBase64, $absolutePath);
                
                if (file_exists($absolutePath) && filesize($absolutePath) > 0) {
                    return $absolutePath;
                }
            } catch (\Exception $pngException) {
                // PNG generation failed, try SVG instead
            }
            
            // Fallback to SVG (works without imagick)
            $svgPath = str_replace('.png', '.svg', $absolutePath);
            QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->errorCorrection('H')
                ->encoding('UTF-8')
                ->generate($qrCodeBase64, $svgPath);
            
            if (file_exists($svgPath)) {
                $this->info('Note: QR code saved as SVG. Install imagick extension for PNG format.');
                return $svgPath;
            }
            
            return null;
        } catch (\Exception $e) {
            $this->error('Failed to generate QR code: '.$e->getMessage());
            return null;
        }
    }
}

