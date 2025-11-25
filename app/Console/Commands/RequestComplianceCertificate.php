<?php

namespace App\Console\Commands;

use App\Services\ZatcaApiService;
use Illuminate\Console\Command;
use Saleh7\Zatca\Exceptions\ZatcaApiException;

class RequestComplianceCertificate extends Command
{
    protected $signature = 'zatca:request-compliance-certificate {otp?}';
    protected $description = 'Request compliance certificate from ZATCA using OTP (can be provided as argument or in .env as ZATCA_OTP)';

    public function handle(ZatcaApiService $service): int
    {
        $otp = $this->argument('otp') ?? config('zatca.otp');
        
        if (empty($otp)) {
            $this->error('OTP is required.');
            $this->line('  Provide it as argument: php artisan zatca:request-compliance-certificate {otp}');
            $this->line('  Or set ZATCA_OTP in .env file');
            return Command::FAILURE;
        }

        $this->info('Requesting compliance certificate from ZATCA...');

        try {
            $result = $service->requestComplianceCertificate($otp);

            $this->info('Compliance certificate received successfully!');
            $this->newLine();
            $this->line('Request ID: ' . $result['request_id']);
            $this->line('Certificate saved to: storage/app/zatca/certificates/ZATCA_certificate_data.json');
            $this->newLine();
            $this->info('You can now sign invoices!');

            return Command::SUCCESS;
        } catch (ZatcaApiException $e) {
            $this->error('Failed to request compliance certificate');
            $this->line('Error: ' . $e->getMessage());
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

