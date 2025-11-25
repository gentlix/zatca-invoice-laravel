<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Sevaske\ZatcaApi\Api;
use Sevaske\ZatcaApi\Exceptions\ZatcaException;

class ZatcaRequestComplianceCertificate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zatca:request-compliance-certificate {otp? : The OTP received from ZATCA}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Request Compliance Certificate from ZATCA using the generated CSR';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $otp = $this->argument('otp') ?? $this->ask('Enter the OTP provided by ZATCA');
        $environment = Config::get('zatca.environment', 'sandbox');

        $storage = Storage::disk('local');
        $directory = 'zatca/output';
        $storage->makeDirectory($directory);

        $csrRelativePath = $directory.'/certificate.csr';
        if (! $storage->exists($csrRelativePath)) {
            $this->error('CSR file not found. Generate it first via `php artisan zatca:generate-csr`.');

            return Command::FAILURE;
        }

        $csr = $storage->get($csrRelativePath);

        $api = new Api($environment, new Client());

        try {
            $this->info('CSR loaded successfully.');
            $this->info('Requesting compliance certificate from ZATCA...');

            $response = $api->complianceCertificate($csr, $otp);

            $credentials = [
                'requestId' => $response->requestId(),
                'certificate' => $response->certificate(),
                'secret' => $response->secret(),
            ];

            $this->table(
                ['Request ID', 'Secret'],
                [[ $credentials['requestId'], $credentials['secret'] ]]
            );

            $outputFile = $storage->path($directory.'/ZATCA_certificate_data.json');
            $storage->put($directory.'/ZATCA_certificate_data.json', json_encode($credentials, JSON_PRETTY_PRINT));

            $this->info("Certificate data saved to {$outputFile}");

            return Command::SUCCESS;
        } catch (ZatcaException $e) {
            $this->error('API Error: '.$e->getMessage());
            if ($context = $e->context()) {
                $this->line(print_r($context, true));
            }

            return Command::FAILURE;
        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}

