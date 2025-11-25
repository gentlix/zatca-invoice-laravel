<?php

namespace App\Console\Commands;

use App\Data\SampleInvoices;
use App\Services\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ZatcaGenerateInvoiceFromArray extends Command
{
    protected $signature = 'zatca:generate-invoice-from-array {invoice_number=1 : Invoice number (1 or 2)}';

    protected $description = 'Generate invoice XML from PHP array data';

    public function handle(): int
    {
        $invoiceNumber = (int) $this->argument('invoice_number');
        
        if (!in_array($invoiceNumber, [1, 2])) {
            $this->error('Invoice number must be 1 or 2');
            return SymfonyCommand::FAILURE;
        }

        $this->info("Generating Invoice #{$invoiceNumber} from array data...");

        // Get sample invoice data
        $invoiceData = $invoiceNumber === 1 
            ? SampleInvoices::getInvoice1() 
            : SampleInvoices::getInvoice2();

        try {
            $service = new InvoiceService();
            
            // Generate XML
            $xmlContent = $service->generateFromArray($invoiceData);
            
            // Save to file
            $disk = Storage::disk('local');
            $disk->makeDirectory('zatca/output');
            $filename = "Invoice_{$invoiceData['invoice_id']}.xml";
            $filePath = "zatca/output/{$filename}";
            $disk->put($filePath, $xmlContent);
            
            $absolutePath = $disk->path($filePath);
            
            $this->info("✓ Invoice generated successfully!");
            $this->info("  Invoice ID: {$invoiceData['invoice_id']}");
            $this->info("  File saved to: {$absolutePath}");
            
            return SymfonyCommand::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error generating invoice: '.$e->getMessage());
            $this->line("File: {$e->getFile()}:{$e->getLine()}");
            
            return SymfonyCommand::FAILURE;
        }
    }
}

