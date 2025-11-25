<?php

namespace App\Console\Commands;

use App\Services\ZatcaInvoiceService;
use Illuminate\Console\Command;

class GenerateSampleInvoices extends Command
{
    protected $signature = 'zatca:generate-sample-invoices';
    protected $description = 'Generate sample simplified and standard invoices';

    public function handle(ZatcaInvoiceService $service): int
    {
        $this->info('Generating sample invoices...');

        try {
            $timestamp = date('YmdHis');

            $this->line('Generating Simplified Invoice...');
            $simplifiedData = $service->getSampleSimplifiedInvoice();
            $simplifiedResult = $service->generateInvoice(
                $simplifiedData,
                'unsigned_Simplified_Invoice_' . $timestamp . '.xml'
            );
            $this->info('Simplified Invoice: ' . basename($simplifiedResult['path']));

            $this->line('Generating Standard Invoice...');
            $standardData = $service->getSampleStandardInvoice();
            $standardResult = $service->generateInvoice(
                $standardData,
                'unsigned_Standard_Invoice_' . $timestamp . '.xml'
            );
            $this->info('Standard Invoice: ' . basename($standardResult['path']));

            $this->newLine();
            $this->info('Sample invoices generated successfully!');
            $this->line('Next step: Run php artisan zatca:sign-invoices to sign them');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate invoices: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }
}

