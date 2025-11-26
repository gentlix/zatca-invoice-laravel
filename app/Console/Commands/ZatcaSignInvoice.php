<?php

namespace App\Console\Commands;

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
    protected $signature = 'zatca:sign-invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sign both Standard and Simplified ZATCA invoices with the compliance certificate';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Signing both Standard and Simplified invoices...');
        $this->newLine();

        $disk = LaravelStorage::disk('local');
        $directory = 'zatca/output';
        $disk->makeDirectory($directory);

        // Load certificate once for both invoices
        $certificateJsonPath = $directory.'/ZATCA_certificate_data.json';
        if (! $disk->exists($certificateJsonPath)) {
            $this->error('Certificate data not found. Request a compliance certificate first.');
            return SymfonyCommand::FAILURE;
        }

        $privateKeyPath = $disk->path($directory.'/private.pem');
        if (! $disk->exists($directory.'/private.pem')) {
            $this->error('Private key not found. Generate it via `php artisan zatca:generate-csr`.');
            return SymfonyCommand::FAILURE;
        }

        $zatcaStorage = new ZatcaStorage();
        $certificateAbsolutePath = $disk->path($certificateJsonPath);
        $jsonCertificate = $zatcaStorage->get($certificateAbsolutePath);
        $jsonData = json_decode($jsonCertificate, true, 512, JSON_THROW_ON_ERROR);

        $certificateContent = $jsonData['certificate'] ?? null;
        $secret = $jsonData['secret'] ?? null;

        if (! $certificateContent || ! $secret) {
            $this->error('Invalid certificate data file.');
            return SymfonyCommand::FAILURE;
        }

        $privateKey = file_get_contents($privateKeyPath);
        $cleanPrivateKey = trim(str_replace(
            ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n"],
            '',
            $privateKey
        ));

        $certificate = new Certificate($certificateContent, $cleanPrivateKey, $secret);
        $this->info('Certificate and private key loaded successfully.');
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        // Define invoices to sign
        $invoicesToSign = [
            'Standard_Invoice.xml' => 'Standard',
            'Simplified_Invoice.xml' => 'Simplified',
        ];

        // Also check for array-generated invoices
        if ($disk->exists($directory.'/Invoice_INV-001.xml')) {
            $invoicesToSign['Invoice_INV-001.xml'] = 'Standard (Array)';
        }
        if ($disk->exists($directory.'/Invoice_INV-002.xml')) {
            $invoicesToSign['Invoice_INV-002.xml'] = 'Simplified (Array)';
        }

        foreach ($invoicesToSign as $invoiceFileName => $invoiceLabel) {
            try {
                $invoiceRelativePath = $directory.'/'.$invoiceFileName;
                if (! $disk->exists($invoiceRelativePath)) {
                    $this->warn("  ⚠ {$invoiceLabel} invoice not found: {$invoiceFileName}");
                    continue;
                }

                $this->info("📄 Signing {$invoiceLabel} Invoice: {$invoiceFileName}");

                $invoiceAbsolutePath = $disk->path($invoiceRelativePath);
                $xmlInvoice = $zatcaStorage->get($invoiceAbsolutePath);

                $this->info('  Signing the invoice...');
                $signer = InvoiceSigner::signInvoice($xmlInvoice, $certificate);
                $signedInvoice = $signer->getXML();

                $signedFileName = str_replace('.xml', '_signed.xml', $invoiceFileName);
                $signedRelativePath = $directory.'/'.$signedFileName;
                $disk->put($signedRelativePath, $signedInvoice);

                // Generate and save QR code image
                $this->info('  Generating QR code image...');
                $invoiceId = pathinfo($invoiceFileName, PATHINFO_FILENAME);
                $qrCodeBase64 = $signer->getQRCode();
                $qrCodePath = $this->generateQrCodeImage($qrCodeBase64, $invoiceId, $disk, $directory);

                if ($qrCodePath) {
                    $this->info("  ✓ QR code image saved to: {$qrCodePath}");
                } else {
                    $this->warn('  ⚠ Could not generate QR code image.');
                }

                $this->info("  ✓ {$invoiceLabel} Invoice signed successfully!");
                $this->info("    Signed invoice: {$signedFileName}");
                $successCount++;
                $this->newLine();
            } catch (\Throwable $e) {
                $this->error("  ✗ Error signing {$invoiceLabel} invoice: ".$e->getMessage());
                $this->line("    File: {$e->getFile()}:{$e->getLine()}");
                $failCount++;
                $this->newLine();
            }
        }

        // Summary
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        if ($successCount > 0) {
            $this->info("✓ Successfully signed {$successCount} invoice(s)!");
            if ($failCount > 0) {
                $this->warn("⚠ {$failCount} invoice(s) failed to sign");
            }
            return SymfonyCommand::SUCCESS;
        } else {
            $this->error("✗ No invoices were signed. {$failCount} failed.");
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
                $this->line('    Note: QR code saved as SVG. Install imagick extension for PNG format.');
                return $svgPath;
            }
            
            return null;
        } catch (\Exception $e) {
            $this->error('    Failed to generate QR code: '.$e->getMessage());
            return null;
        }
    }
}
