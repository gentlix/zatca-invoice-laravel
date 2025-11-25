<?php

namespace App\Console\Commands;

use App\Services\ZatcaSigningService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;

class SignInvoices extends Command
{
    protected $signature = 'zatca:sign-invoices {--file= : Specific file to sign}';
    protected $description = 'Sign unsigned invoices and generate QR codes';

    public function handle(ZatcaSigningService $signingService): int
    {
        $this->info('Signing invoices...');

        try {
            $invoicesPath = config('zatca.paths.invoices');
            $this->line("Looking for invoices in: {$invoicesPath}");
            
            $files = $this->getFilesToSign($invoicesPath);
            $this->line("Found " . count($files) . " file(s) to sign");

            if (empty($files)) {
                $this->warn('No unsigned invoices found.');
                $fullPath = storage_path('app/' . $invoicesPath);
                $this->line("Searching in: {$fullPath}");
                if (is_dir($fullPath)) {
                    $pattern = $fullPath . '/*.xml';
                    $foundFiles = glob($pattern);
                    $this->line("XML files found: " . count($foundFiles));
                    foreach ($foundFiles as $f) {
                        $basename = basename($f);
                        $isUnsigned = str_starts_with($basename, 'unsigned_');
                        $hasSigned = str_contains($basename, 'signed') && !str_starts_with($basename, 'unsigned_');
                        $this->line("  - {$basename} (unsigned_: " . ($isUnsigned ? 'yes' : 'no') . ", signed: " . ($hasSigned ? 'yes' : 'no') . ")");
                    }
                } else {
                    $this->error("Directory does not exist: {$fullPath}");
                }
                return Command::SUCCESS;
            }

            $signedCount = 0;
            foreach ($files as $file) {
                $filename = basename($file);
                $this->line("Signing: {$filename}");

                $xmlContent = Storage::get($file);
                $signedFilename = $this->getSignedFilename($filename);
                $result = $signingService->signInvoice($xmlContent, $signedFilename);

                $this->info("Signed: {$filename}");
                $this->line("  Hash: " . substr($result['hash'], 0, 20) . '...');
                $signedCount++;
            }

            $this->newLine();
            $this->info("Successfully signed {$signedCount} invoice(s)!");
            $this->line('Next step: Run php artisan zatca:submit-invoice {uuid} to submit to ZATCA');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to sign invoices: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    protected function getFilesToSign(string $invoicesPath): array
    {
        if ($this->option('file')) {
            $filePath = $this->option('file');
            if (!str_starts_with($filePath, $invoicesPath)) {
                $filePath = $invoicesPath . '/' . basename($filePath);
            }
            if (Storage::exists($filePath)) {
                return [$filePath];
            }
            return [];
        }

        $fullPath = storage_path('app/' . $invoicesPath);
        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        $pattern = $fullPath . '/*.xml';
        $foundFiles = glob($pattern);
        
        foreach ($foundFiles as $filePath) {
            if (is_dir($filePath)) {
                continue;
            }
            
            $filename = basename($filePath);
            if (str_starts_with($filename, 'unsigned_') || 
                (!str_starts_with($filename, 'signed_') && !str_contains($filename, '_signed'))) {
                $files[] = $invoicesPath . '/' . $filename;
            }
        }

        return $files;
    }

    protected function getSignedFilename(string $filename): string
    {
        $filename = str_replace('unsigned_', '', $filename);
        return 'signed_' . $filename;
    }
}

