<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage as LaravelStorage;
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
            $signedInvoice = InvoiceSigner::signInvoice($xmlInvoice, $certificate)->getXML();

            $signedFileName = str_replace('.xml', '_signed.xml', $invoiceFileName);
            $signedRelativePath = $directory.'/'.$signedFileName;
            $disk->put($signedRelativePath, $signedInvoice);

            $this->info('Invoice signed successfully!');
            $this->info('Signed invoice saved to: '.$disk->path($signedRelativePath));

            return SymfonyCommand::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());
            $this->line('Debug: '.$e->getFile().':'.$e->getLine());

            return SymfonyCommand::FAILURE;
        }
    }
}

