<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanZatcaFiles extends Command
{
    protected $signature = 'zatca:clean 
                            {--invoices : Clean invoice files (unsigned and signed)}
                            {--qr-codes : Clean QR code files}
                            {--certificates : Clean certificate files (CSR, private key, certificate data)}
                            {--all : Clean all generated files}';

    protected $description = 'Clean generated ZATCA files (invoices, QR codes, certificates)';

    public function handle(): int
    {
        $cleanAll = $this->option('all');
        $cleanInvoices = $this->option('invoices') || $cleanAll;
        $cleanQrCodes = $this->option('qr-codes') || $cleanAll;
        $cleanCertificates = $this->option('certificates') || $cleanAll;

        if (!$cleanInvoices && !$cleanQrCodes && !$cleanCertificates) {
            $this->error('Please specify what to clean. Use --invoices, --qr-codes, --certificates, or --all');
            $this->line('');
            $this->line('Examples:');
            $this->line('  php artisan zatca:clean --invoices');
            $this->line('  php artisan zatca:clean --all');
            return Command::FAILURE;
        }

        if ($cleanCertificates && !$this->confirm('Are you sure you want to delete certificate files? This cannot be undone.', true)) {
            $this->line('Cancelled.');
            return Command::SUCCESS;
        }

        $deletedCount = 0;

        if ($cleanInvoices) {
            $deletedCount += $this->cleanInvoices();
        }

        if ($cleanQrCodes) {
            $deletedCount += $this->cleanQrCodes();
        }

        if ($cleanCertificates) {
            $deletedCount += $this->cleanCertificates();
        }

        $this->newLine();
        $this->info("Cleaned {$deletedCount} file(s)");

        return Command::SUCCESS;
    }

    protected function cleanInvoices(): int
    {
        $invoicesPath = config('zatca.paths.invoices');
        $signedPath = config('zatca.paths.invoices_signed');

        $count = 0;

        if (Storage::exists($invoicesPath)) {
            $files = Storage::allFiles($invoicesPath);
            foreach ($files as $file) {
                if (Storage::exists($file)) {
                    Storage::delete($file);
                    $count++;
                }
            }
        }

        if (Storage::exists($signedPath)) {
            $files = Storage::allFiles($signedPath);
            foreach ($files as $file) {
                if (Storage::exists($file)) {
                    Storage::delete($file);
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->line("Deleted {$count} invoice file(s)");
        } else {
            $this->line("No invoice files found");
        }

        return $count;
    }

    protected function cleanQrCodes(): int
    {
        $qrCodesPath = config('zatca.paths.qr_codes');
        $count = 0;

        if (Storage::exists($qrCodesPath)) {
            $files = Storage::allFiles($qrCodesPath);
            foreach ($files as $file) {
                if (Storage::exists($file)) {
                    Storage::delete($file);
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->line("Deleted {$count} QR code file(s)");
        } else {
            $this->line("No QR code files found");
        }

        return $count;
    }

    protected function cleanCertificates(): int
    {
        $certificatesPath = config('zatca.paths.certificates');
        $count = 0;

        if (Storage::exists($certificatesPath)) {
            $files = Storage::allFiles($certificatesPath);
            foreach ($files as $file) {
                if (Storage::exists($file)) {
                    Storage::delete($file);
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->line("Deleted {$count} certificate file(s)");
        } else {
            $this->line("No certificate files found");
        }

        return $count;
    }
}

