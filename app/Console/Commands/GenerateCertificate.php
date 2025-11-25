<?php

namespace App\Console\Commands;

use App\Services\ZatcaCertificateService;
use Illuminate\Console\Command;
use Saleh7\Zatca\Exceptions\CertificateBuilderException;

class GenerateCertificate extends Command
{
    protected $signature = 'zatca:generate-certificate';
    protected $description = 'Generate CSR and private key for ZATCA certificate';

    public function handle(ZatcaCertificateService $service): int
    {
        $this->info('Generating certificate...');

        try {
            $result = $service->generateCertificate();

            $this->info('Certificate generated successfully!');
            $this->newLine();
            $this->line('CSR Path: ' . $result['csr_path']);
            $this->line('Private Key Path: ' . str_replace('certificate.csr', 'private.pem', $result['csr_path']));
            $this->newLine();
            $this->info('Next steps:');
            $this->line('  1. Upload the CSR file to ZATCA portal');
            $this->line('  2. Get the OTP from ZATCA');
            $this->line('  3. Run: php artisan zatca:request-compliance-certificate {otp}');

            return Command::SUCCESS;
        } catch (CertificateBuilderException $e) {
            $this->error('Certificate generation failed: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }
}

