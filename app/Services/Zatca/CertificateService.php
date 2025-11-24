<?php

namespace App\Services\Zatca;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CertificateService
{
    /**
     * Generate CSR (Certificate Signing Request)
     */
    public function generateCSR(array $sellerInfo): array
    {
        try {
            $dn = [
                "countryName" => "SA",
                "stateOrProvinceName" => $sellerInfo['state'] ?? 'Riyadh',
                "localityName" => $sellerInfo['city'] ?? 'Riyadh',
                "organizationName" => $sellerInfo['name'],
                "organizationalUnitName" => $sellerInfo['unit'] ?? 'IT',
                "commonName" => $sellerInfo['vat_registration_number'],
                "emailAddress" => $sellerInfo['email'] ?? 'info@example.com'
            ];

            // Find OpenSSL configuration file (required on Windows)
            $opensslConf = $this->findOpenSSLConfig();

            $config = [
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
                "digest_alg" => "sha256",
            ];

            // Add config file path if found (fixes Windows error)
            if ($opensslConf) {
                $config["config"] = $opensslConf;
            }

            $privateKey = openssl_pkey_new($config);

            if (!$privateKey) {
                // Get all OpenSSL errors for better debugging
                $errors = [];
                while (($error = openssl_error_string()) !== false) {
                    $errors[] = $error;
                }
                $errorMessage = !empty($errors) ? implode('; ', $errors) : 'Unknown OpenSSL error';
                throw new \Exception('Failed to generate private key: ' . $errorMessage);
            }

            $csr = openssl_csr_new($dn, $privateKey, $config);

            if (!$csr) {
                $errors = [];
                while (($error = openssl_error_string()) !== false) {
                    $errors[] = $error;
                }
                $errorMessage = !empty($errors) ? implode('; ', $errors) : 'Unknown OpenSSL error';
                throw new \Exception('Failed to generate CSR: ' . $errorMessage);
            }

            openssl_csr_export($csr, $csrOut);
            
            // Pass config to pkey_export for Windows compatibility
            if ($opensslConf) {
                openssl_pkey_export($privateKey, $privateKeyOut, null, $config);
            } else {
                openssl_pkey_export($privateKey, $privateKeyOut);
            }

            // Create certificates directory if it doesn't exist
            if (!Storage::disk('local')->exists('certificates')) {
                Storage::disk('local')->makeDirectory('certificates');
            }

            // Save to storage
            Storage::disk('local')->put('certificates/csr.pem', $csrOut);
            Storage::disk('local')->put('certificates/private_key.pem', $privateKeyOut);

            return [
                'success' => true,
                'csr' => $csrOut,
                'private_key' => $privateKeyOut,
                'csr_path' => storage_path('app/certificates/csr.pem'),
                'private_key_path' => storage_path('app/certificates/private_key.pem'),
                'message' => 'CSR generated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('CSR Generation Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Save certificate from ZATCA
     */
    public function saveCertificate(string $certificateContent): array
    {
        try {
            if (!Storage::disk('local')->exists('certificates')) {
                Storage::disk('local')->makeDirectory('certificates');
            }

            Storage::disk('local')->put('certificates/cert.pem', $certificateContent);
            
            return [
                'success' => true,
                'certificate_path' => storage_path('app/certificates/cert.pem'),
                'message' => 'Certificate saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Certificate Save Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if certificate and private key exist
     */
    public function hasCertificate(): bool
    {
        $certPath = config('zatca.certificate_path');
        $keyPath = config('zatca.private_key_path');
        
        return file_exists($certPath) && file_exists($keyPath);
    }

    /**
     * Find OpenSSL configuration file
     * This is required on Windows to avoid "No such process" error
     */
    private function findOpenSSLConfig(): ?string
    {
        // Check PHP's openssl.cnf setting first
        $opensslConf = ini_get('openssl.cnf');
        if (!empty($opensslConf) && file_exists($opensslConf)) {
            return $opensslConf;
        }

        // Check OPENSSL_CONF environment variable
        $envConf = getenv('OPENSSL_CONF');
        if ($envConf && file_exists($envConf)) {
            return $envConf;
        }

        // Common Windows locations
        $possiblePaths = [
            // XAMPP
            'C:/xampp/apache/bin/openssl.cnf',
            'C:/xampp/php/extras/ssl/openssl.cnf',
            // WAMP
            'C:/wamp64/bin/php/php8.1.0/extras/ssl/openssl.cnf',
            'C:/wamp64/bin/php/php8.2.0/extras/ssl/openssl.cnf',
            // Laragon
            'C:/laragon/bin/php/php8.1.0/extras/ssl/openssl.cnf',
            'C:/laragon/bin/php/php8.2.0/extras/ssl/openssl.cnf',
            // Standard PHP installation
            'C:/php/extras/ssl/openssl.cnf',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // If not found, create a minimal temporary config file
        return $this->createTempOpenSSLConfig();
    }

    /**
     * Create a temporary minimal OpenSSL config file
     */
    private function createTempOpenSSLConfig(): ?string
    {
        try {
            $tempConfig = storage_path('app/temp_openssl.cnf');
            
            $configContent = <<<'CONFIG'
[req]
distinguished_name = req_distinguished_name
req_extensions = v3_req
prompt = no

[req_distinguished_name]
countryName = SA

[v3_req]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
CONFIG;

            file_put_contents($tempConfig, $configContent);
            
            if (file_exists($tempConfig)) {
                return $tempConfig;
            }
        } catch (\Exception $e) {
            Log::warning('Could not create temporary OpenSSL config', ['error' => $e->getMessage()]);
        }

        return null;
    }
}

