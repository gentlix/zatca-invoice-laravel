<?php

namespace App\Console\Commands;

use App\Data\SampleInvoices;
use App\Services\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ZatcaGenerateInvoiceFromArray extends Command
{
    protected $signature = 'zatca:generate-invoice-from-array';

    protected $description = 'Generate both Standard and Simplified invoices from PHP array data';

    public function handle(): int
    {
        $this->info('Generating both Standard and Simplified invoices from array data...');
        $this->newLine();

        $service = new InvoiceService();
        $disk = Storage::disk('local');
        $disk->makeDirectory('zatca/output');

        $successCount = 0;
        $failCount = 0;

        // Generate Standard Invoice (Invoice 1)
        try {
            $this->info('📄 Generating STANDARD Invoice...');
            $invoiceData1 = SampleInvoices::getInvoice1();
            $xmlContent1 = $service->generateFromArray($invoiceData1);
            
            $filename1 = "Invoice_{$invoiceData1['invoice_id']}.xml";
            $filePath1 = "zatca/output/{$filename1}";
            $disk->put($filePath1, $xmlContent1);
            
            $absolutePath1 = $disk->path($filePath1);
            $this->info("  ✓ STANDARD Invoice generated successfully!");
            $this->info("    Invoice ID: {$invoiceData1['invoice_id']}");
            $this->info("    File saved to: {$absolutePath1}");
            $successCount++;
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("  ✗ Error generating STANDARD invoice: ".$e->getMessage());
            $this->line("    File: {$e->getFile()}:{$e->getLine()}");
            $failCount++;
            $this->newLine();
        }

        // Generate Simplified Invoice (Invoice 2)
        try {
            $this->info('📄 Generating SIMPLIFIED Invoice...');
            $invoiceData2 = SampleInvoices::getInvoice2();
            $xmlContent2 = $service->generateFromArray($invoiceData2);
            
            $filename2 = "Invoice_{$invoiceData2['invoice_id']}.xml";
            $filePath2 = "zatca/output/{$filename2}";
            $disk->put($filePath2, $xmlContent2);
            
            $absolutePath2 = $disk->path($filePath2);
            $this->info("  ✓ SIMPLIFIED Invoice generated successfully!");
            $this->info("    Invoice ID: {$invoiceData2['invoice_id']}");
            $this->info("    File saved to: {$absolutePath2}");
            $successCount++;
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("  ✗ Error generating SIMPLIFIED invoice: ".$e->getMessage());
            $this->line("    File: {$e->getFile()}:{$e->getLine()}");
            $failCount++;
            $this->newLine();
        }

        // Summary
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        if ($successCount === 2) {
            $this->info("✓ Successfully generated both invoices!");
            $this->info("  • Standard Invoice: Invoice_INV-001.xml");
            $this->info("  • Simplified Invoice: Invoice_INV-002.xml");
            return SymfonyCommand::SUCCESS;
        } else {
            $this->warn("⚠ Generated {$successCount} invoice(s), {$failCount} failed");
            return $failCount > 0 ? SymfonyCommand::FAILURE : SymfonyCommand::SUCCESS;
        }
    }
}
