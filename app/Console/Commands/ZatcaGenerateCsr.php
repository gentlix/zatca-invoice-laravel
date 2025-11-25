<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Saleh7\Zatca\CertificateBuilder;
use Saleh7\Zatca\Exceptions\CertificateBuilderException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ZatcaGenerateCsr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zatca:generate-csr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ZATCA CSR (Certificate Signing Request) and Private Key';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting ZATCA CSR and Private Key generation...');

        $disk = Storage::disk('local');
        $outputDirectory = 'zatca/output';
        $disk->makeDirectory($outputDirectory);
        
        // Get absolute path and ensure it exists
        $absolutePath = $disk->path($outputDirectory);
        File::ensureDirectoryExists($absolutePath);
        
        // Use absolute paths with proper directory separators
        $csrPath = realpath($absolutePath) ?: $absolutePath;
        $csrPath = rtrim($csrPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'certificate.csr';
        $privateKeyPath = realpath($absolutePath) ?: $absolutePath;
        $privateKeyPath = rtrim($privateKeyPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'private.pem';
        
        // Ensure paths are absolute and normalized
        $csrPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $csrPath);
        $privateKeyPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $privateKeyPath);

        // Create a minimal OpenSSL config file for Windows
        $opensslConfigPath = $this->createOpenSslConfigFile();

        try {
            $builder = (new CertificateBuilder())
                ->setOrganizationIdentifier(Config::get('zatca.organization.identifier'))
                ->setSerialNumber(
                    Config::get('zatca.device.solution_name'),
                    Config::get('zatca.device.model'),
                    Config::get('zatca.device.serial_number'),
                )
                ->setCommonName(Config::get('zatca.organization.name'))
                ->setCountryName(Config::get('zatca.organization.country_code'))
                ->setOrganizationName(Config::get('zatca.organization.name'))
                ->setOrganizationalUnitName(Config::get('zatca.organization.unit'))
                ->setAddress(Config::get('zatca.organization.address'))
                ->setInvoiceType(Config::get('zatca.invoice_type'))
                ->setProduction(Config::get('zatca.is_production'))
                ->setBusinessCategory(Config::get('zatca.organization.business_category'));
            
            // Generate the CSR and private key
            $builder->generate();
            
            // Get CSR content and save it
            $csrContent = $builder->getCsr();
            File::put($csrPath, $csrContent);
            
            // Export private key using reflection to access the private key resource
            // and export it with our config file (workaround for Windows OpenSSL issue)
            $reflection = new \ReflectionClass($builder);
            $privateKeyProperty = $reflection->getProperty('privateKey');
            $privateKeyProperty->setAccessible(true);
            $privateKeyResource = $privateKeyProperty->getValue($builder);
            
            $privateKeyContent = null;
            $config = ['config' => $opensslConfigPath];
            if (! openssl_pkey_export($privateKeyResource, $privateKeyContent, null, $config)) {
                $error = openssl_error_string();
                throw new CertificateBuilderException('Private key export failed: '.($error ?: 'Unknown OpenSSL error'));
            }
            File::put($privateKeyPath, $privateKeyContent);

            $this->info("✓ Certificate saved to: {$csrPath}");
            $this->info("✓ Private key saved to: {$privateKeyPath}");

            return SymfonyCommand::SUCCESS;
        } catch (CertificateBuilderException $e) {
            $this->error('Error generating CSR: '.$e->getMessage());

            return SymfonyCommand::FAILURE;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return SymfonyCommand::FAILURE;
        } finally {
            // Clean up OpenSSL config file
            if (isset($opensslConfigPath) && file_exists($opensslConfigPath)) {
                @unlink($opensslConfigPath);
            }
        }
    }

    /**
     * Create a minimal OpenSSL config file for Windows compatibility.
     */
    private function createOpenSslConfigFile(): string
    {
        $configContent = <<<'EOL'
[default]
openssl_conf = openssl_init

[openssl_init]
engines = engine_section

[engine_section]
EOL;

        $tempFile = tempnam(sys_get_temp_dir(), 'openssl_');
        File::put($tempFile, $configContent);

        return $tempFile;
    }
}

