<?php

namespace App\Console\Commands;

use App\Services\ZatcaApiService;
use App\Services\ZatcaSigningService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;
use Saleh7\Zatca\Exceptions\ZatcaApiException;

class SubmitInvoice extends Command
{
    protected $signature = 'zatca:submit-invoice {uuid}';
    protected $description = 'Submit signed invoice to ZATCA API';

    public function handle(ZatcaApiService $apiService, ZatcaSigningService $signingService): int
    {
        $uuid = $this->argument('uuid');
        $this->info("Submitting invoice to ZATCA (UUID: {$uuid})...");

        try {
            $signedInvoice = $this->findSignedInvoiceByUuid($uuid);
            if (!$signedInvoice) {
                return Command::FAILURE;
            }

            $hash = $signingService->extractHashFromSignedXml($signedInvoice);
            $this->line("Invoice hash: " . substr($hash, 0, 20) . '...');

            $response = $apiService->submitInvoice($signedInvoice, $hash, $uuid);

            $this->newLine();
            $this->info('Invoice submitted successfully!');
            $this->line('Response: ' . json_encode($response, JSON_PRETTY_PRINT));
            $this->newLine();
            $this->line('Response logged to database (zatca_logs table)');

            return Command::SUCCESS;
        } catch (ZatcaApiException $e) {
            return $this->handleApiException($e);
        } catch (\Exception $e) {
            return $this->handleGenericException($e);
        }
    }

    protected function findSignedInvoiceByUuid(string $uuid): ?string
    {
        $signedInvoicesPath = config('zatca.paths.invoices_signed');
        $files = Storage::files($signedInvoicesPath);
        
        foreach ($files as $file) {
            $content = Storage::get($file);
            if (str_contains($content, $uuid)) {
                return $content;
            }
        }

        $this->error("Signed invoice with UUID {$uuid} not found.");
        $this->line("Searched in: " . storage_path('app/' . $signedInvoicesPath));
        return null;
    }

    protected function handleApiException(ZatcaApiException $e): int
    {
        $this->newLine();
        $this->error('Failed to submit invoice to ZATCA API');
        $this->line('Error: ' . $e->getMessage());
        
        if (method_exists($e, 'getContext')) {
            $context = $e->getContext();
            if (!empty($context)) {
                $this->line('Details: ' . json_encode($context, JSON_PRETTY_PRINT));
            }
        }
        
        $this->newLine();
        $this->line('Common issues:');
        $this->line('  - Check ZATCA_ENVIRONMENT in .env (sandbox/simulation/production)');
        $this->line('  - Verify certificate is valid and not expired');
        $this->line('  - Check network connectivity to ZATCA servers');
        $this->line('  - Review error details in zatca_logs table');
        
        $this->line('Error logged to database (zatca_logs table)');
        return Command::FAILURE;
    }

    protected function handleGenericException(\Exception $e): int
    {
        $this->newLine();
        $this->error('Failed to submit invoice');
        $this->line('Error: ' . $e->getMessage());
        
        if ($this->option('verbose')) {
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
        
        $this->line('Error logged to database (zatca_logs table)');
        return Command::FAILURE;
    }
}

