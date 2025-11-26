<?php

namespace App\Console\Commands;

use App\Models\ZatcaSubmission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ZatcaCleanHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zatca:clean-history 
                            {--invoices : Clean unsigned and signed invoice XML files}
                            {--qr-codes : Clean QR code images}
                            {--database : Clean database submission logs}
                            {--all : Clean all history (invoices, QR codes, and database logs)}
                            {--days=30 : Number of days to keep (default: 30)}
                            {--force : Force clean all files regardless of age}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean ZATCA history (invoices, QR codes, and database logs)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cleanInvoices = $this->option('invoices') || $this->option('all');
        $cleanQrCodes = $this->option('qr-codes') || $this->option('all');
        $cleanDatabase = $this->option('database') || $this->option('all');
        $force = $this->option('force');
        $days = (int) $this->option('days');

        // If no options specified, show help
        if (! $cleanInvoices && ! $cleanQrCodes && ! $cleanDatabase) {
            $this->info('ZATCA History Cleanup');
            $this->newLine();
            $this->info('Available options:');
            $this->line('  --invoices    Clean unsigned and signed invoice XML files');
            $this->line('  --qr-codes    Clean QR code images');
            $this->line('  --database    Clean database submission logs');
            $this->line('  --all         Clean all history');
            $this->line('  --days=30     Number of days to keep (default: 30)');
            $this->line('  --force       Force clean all files regardless of age');
            $this->newLine();
            $this->info('Examples:');
            $this->line('  php artisan zatca:clean-history --all');
            $this->line('  php artisan zatca:clean-history --all --force');
            $this->line('  php artisan zatca:clean-history --invoices --days=7');
            $this->line('  php artisan zatca:clean-history --database --days=90');
            
            return SymfonyCommand::SUCCESS;
        }

        if ($force) {
            $this->warn("⚠️  FORCE MODE: This will delete ALL files including certificates (CSR, PEM, JSON)!");
            $this->info("Cleaning ZATCA history (force mode - deleting all producible files)...");
        } else {
            $this->info("Cleaning ZATCA history (keeping files newer than {$days} days)...");
            $this->line("Note: Use --force to also delete certificate files (CSR, PEM, JSON)");
        }
        $this->newLine();

        $totalDeleted = 0;
        $cutoffDate = $force ? now()->addDay() : now()->subDays($days); // If force, set future date so all files are deleted

        // Clean invoice files
        if ($cleanInvoices) {
            $deleted = $this->cleanInvoiceFiles($cutoffDate, $force);
            $totalDeleted += $deleted;
        }

        // Clean QR codes
        if ($cleanQrCodes) {
            $deleted = $this->cleanQrCodes($cutoffDate, $force);
            $totalDeleted += $deleted;
        }

        // Clean database logs
        if ($cleanDatabase) {
            $deleted = $this->cleanDatabaseLogs($cutoffDate, $force);
            $totalDeleted += $deleted;
        }

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("✓ Cleanup completed! Total items deleted: {$totalDeleted}");

        return SymfonyCommand::SUCCESS;
    }

    /**
     * Clean invoice XML files
     */
    private function cleanInvoiceFiles($cutoffDate, bool $force = false): int
    {
        $this->info('📄 Cleaning invoice files...');
        $disk = Storage::disk('local');
        $directory = 'zatca/output';

        if (! $disk->exists($directory)) {
            $this->line('  No invoice directory found.');
            return 0;
        }

        $deletedCount = 0;
        $files = $disk->files($directory);

        foreach ($files as $file) {
            $fileName = basename($file);
            $filePath = $disk->path($file);
            $fileTime = filemtime($filePath);
            $fileDate = \Carbon\Carbon::createFromTimestamp($fileTime);

            // Delete invoice XML files
            if (str_ends_with($file, '.xml')) {
                if ($force || $fileDate->lt($cutoffDate)) {
                    $disk->delete($file);
                    $deletedCount++;
                    $this->line("  ✓ Deleted: {$fileName}");
                }
            }
            // Delete certificate files (CSR, PEM, JSON) if force mode
            elseif ($force && (
                str_contains($file, 'certificate.csr') || 
                str_contains($file, 'private.pem') || 
                str_contains($file, 'ZATCA_certificate_data.json')
            )) {
                $disk->delete($file);
                $deletedCount++;
                $this->line("  ✓ Deleted: {$fileName}");
            }
        }

        if ($deletedCount === 0) {
            $this->line('  No files to delete.');
        } else {
            $this->info("  ✓ Deleted {$deletedCount} file(s)");
        }

        return $deletedCount;
    }

    /**
     * Clean QR code images
     */
    private function cleanQrCodes($cutoffDate, bool $force = false): int
    {
        $this->info('📱 Cleaning QR code images...');
        $disk = Storage::disk('local');
        $directory = 'zatca/qr-codes';

        if (! $disk->exists($directory)) {
            $this->line('  No QR code directory found.');
            return 0;
        }

        $deletedCount = 0;
        $files = $disk->files($directory);

        foreach ($files as $file) {
            $filePath = $disk->path($file);
            $fileTime = filemtime($filePath);
            $fileDate = \Carbon\Carbon::createFromTimestamp($fileTime);

            // If force mode, delete all. Otherwise, only delete files older than cutoff
            if ($force || $fileDate->lt($cutoffDate)) {
                $disk->delete($file);
                $deletedCount++;
                $this->line("  ✓ Deleted: ".basename($file));
            }
        }

        if ($deletedCount === 0) {
            $this->line('  No old QR code files to delete.');
        } else {
            $this->info("  ✓ Deleted {$deletedCount} QR code file(s)");
        }

        return $deletedCount;
    }

    /**
     * Clean database submission logs
     */
    private function cleanDatabaseLogs($cutoffDate, bool $force = false): int
    {
        $this->info('💾 Cleaning database submission logs...');

        try {
            if ($force) {
                $deletedCount = ZatcaSubmission::count();
                ZatcaSubmission::truncate();
            } else {
                $deletedCount = ZatcaSubmission::where('created_at', '<', $cutoffDate)->delete();
            }

            if ($deletedCount === 0) {
                $this->line('  No old database logs to delete.');
            } else {
                $this->info("  ✓ Deleted {$deletedCount} database record(s)");
            }

            return $deletedCount;
        } catch (\Exception $e) {
            $this->error('  ✗ Error cleaning database: '.$e->getMessage());
            return 0;
        }
    }
}
