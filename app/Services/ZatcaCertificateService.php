<?php

namespace App\Services;

use Saleh7\Zatca\CertificateBuilder;
use Saleh7\Zatca\Exceptions\CertificateBuilderException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class ZatcaCertificateService
{
    protected string $certificatesPath;

    public function __construct()
    {
        $this->certificatesPath = config('zatca.paths.certificates');
    }

    public function generateCertificate(): array
    {
        $config = config('zatca');
        
        try {
            $builder = (new CertificateBuilder())
                ->setOrganizationIdentifier($config['organization']['identifier'])
                ->setSerialNumber(
                    $config['certificate']['solution_name'],
                    $config['certificate']['model'],
                    $config['certificate']['serial_number']
                )
                ->setCommonName($config['organization']['name'])
                ->setCountryName($config['organization']['country'])
                ->setOrganizationName($config['organization']['name'])
                ->setOrganizationalUnitName($config['organization']['unit_name'])
                ->setAddress($config['organization']['address'])
                ->setInvoiceType($config['certificate']['invoice_type'])
                ->setProduction($config['certificate']['production'])
                ->setBusinessCategory($config['organization']['business_category']);

            // Generate paths
            $csrPath = $this->certificatesPath . '/certificate.csr';
            $privateKeyPath = $this->certificatesPath . '/private.pem';
            $fullCsrPath = storage_path('app/' . $csrPath);
            $fullPrivateKeyPath = storage_path('app/' . $privateKeyPath);

            // Ensure directory exists
            Storage::makeDirectory($this->certificatesPath);

            $builder->generate();
            
            $csrContent = $builder->getCsr();
            Storage::put($csrPath, $csrContent);
            $reflection = new ReflectionClass($builder);
            $privateKeyProperty = $reflection->getProperty('privateKey');
            $privateKeyProperty->setAccessible(true);
            $privateKeyResource = $privateKeyProperty->getValue($builder);
            
            $privateKeyContent = '';
            $exportSuccess = false;
            
            if (openssl_pkey_export($privateKeyResource, $privateKeyContent)) {
                $exportSuccess = true;
            } else {
                $opensslConfigPath = base_path('openssl.cnf');
                if (file_exists($opensslConfigPath)) {
                    $config = ['config' => $opensslConfigPath];
                    if (openssl_pkey_export($privateKeyResource, $privateKeyContent, null, $config)) {
                        $exportSuccess = true;
                    }
                }
                
                if (!$exportSuccess) {
                    $commonPaths = [
                        'C:/xampp/apache/conf/openssl.cnf',
                        'C:/xampp/php/extras/openssl/openssl.cnf',
                        'C:/OpenSSL-Win64/bin/openssl.cfg',
                        'C:/Program Files/OpenSSL-Win64/bin/openssl.cfg',
                    ];
                    
                    foreach ($commonPaths as $configPath) {
                        if (file_exists($configPath)) {
                            $config = ['config' => $configPath];
                            if (openssl_pkey_export($privateKeyResource, $privateKeyContent, null, $config)) {
                                $exportSuccess = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            if (!$exportSuccess) {
                $privateKeyContent = $this->exportKeyViaCommandLine($privateKeyResource, $fullPrivateKeyPath);
                if (empty($privateKeyContent)) {
                    throw new CertificateBuilderException(
                        'Private key export failed. Please set OPENSSL_CONF environment variable: $env:OPENSSL_CONF = "C:\\xampp\\apache\\conf\\openssl.cnf"'
                    );
                }
            }
            
            // Write private key to file
            file_put_contents($fullPrivateKeyPath, $privateKeyContent);

            Log::info('Certificate generated successfully', [
                'csr_path' => $csrPath,
                'private_key_path' => $privateKeyPath,
            ]);

            return [
                'csr_path' => $csrPath,
                'private_key_path' => $privateKeyPath,
                'csr_content' => Storage::get($csrPath),
            ];
        } catch (CertificateBuilderException $e) {
            Log::error('Certificate generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getCertificatePaths(): array
    {
        return [
            'csr' => storage_path('app/' . $this->certificatesPath . '/certificate.csr'),
            'private_key' => storage_path('app/' . $this->certificatesPath . '/private.pem'),
            'certificate_data' => storage_path('app/' . $this->certificatesPath . '/ZATCA_certificate_data.json'),
        ];
    }

    /**
     * Check if certificates exist
     *
     * @return bool
     */
    public function certificatesExist(): bool
    {
        $paths = $this->getCertificatePaths();
        return file_exists($paths['csr']) && file_exists($paths['private_key']);
    }


    private function exportKeyViaCommandLine($privateKeyResource, string $outputPath): ?string
    {
        // Try to export to a temporary string as last resort
        $tempContent = '';
        if (openssl_pkey_export($privateKeyResource, $tempContent)) {
            return $tempContent;
        }
        
        return null;
    }
}

