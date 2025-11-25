<?php

namespace App\Services;

use Saleh7\Zatca\ZatcaAPI;
use Saleh7\Zatca\Exceptions\ZatcaApiException;
use App\Models\ZatcaLog;
use Illuminate\Support\Facades\Log;

class ZatcaApiService
{
    protected ZatcaAPI $client;
    protected ZatcaCertificateService $certificateService;
    protected ZatcaSigningService $signingService;

    public function __construct(
        ?ZatcaCertificateService $certificateService = null,
        ?ZatcaSigningService $signingService = null
    ) {
        $environment = config('zatca.environment');
        $this->client = new ZatcaAPI($environment);
        $this->certificateService = $certificateService ?? new ZatcaCertificateService();
        $this->signingService = $signingService ?? new ZatcaSigningService();
    }

    public function requestComplianceCertificate(string $otp): array
    {
        try {
            $paths = $this->certificateService->getCertificatePaths();
            $csr = $this->client->loadCSRFromFile($paths['csr']);

            $result = $this->client->requestComplianceCertificate($csr, $otp);

            $outputFile = $paths['certificate_data'];
            $certificate = $result->getCertificate();
            $certificateBase64 = base64_encode($certificate);
            $this->client->saveToJson(
                $certificateBase64,
                $result->getSecret(),
                $result->getRequestId(),
                $outputFile
            );

            $this->logApiCall('request_compliance_certificate', [
                'request_id' => $result->getRequestId(),
                'status' => 'success',
            ]);

            return [
                'certificate' => $result->getCertificate(),
                'secret' => $result->getSecret(),
                'request_id' => $result->getRequestId(),
            ];
        } catch (ZatcaApiException $e) {
            $this->logApiCall('request_compliance_certificate', [
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function submitInvoice(string $signedXml, string $invoiceHash, string $uuid): array
    {
        try {
            $certificateData = $this->signingService->loadCertificateData();
            
            if (empty($certificateData['certificate'])) {
                throw new \Exception('Certificate is empty. Please request compliance certificate first.');
            }
            
            if (empty($certificateData['secret'])) {
                throw new \Exception('Secret is empty. Please request compliance certificate first.');
            }
            
            if (empty($signedXml)) {
                throw new \Exception('Signed XML is empty.');
            }
            
            if (empty($invoiceHash)) {
                throw new \Exception('Invoice hash is empty.');
            }
            
            if (empty($uuid)) {
                throw new \Exception('UUID is empty.');
            }
            
            Log::info('Submitting invoice to ZATCA', [
                'uuid' => $uuid,
                'hash_length' => strlen($invoiceHash),
                'hash_preview' => substr($invoiceHash, 0, 20) . '...',
                'xml_length' => strlen($signedXml),
            ]);
            
            $response = $this->client->validateInvoiceCompliance(
                $certificateData['certificate'],
                $certificateData['secret'],
                $signedXml,
                $invoiceHash,
                $uuid
            );

            $this->logApiCall('validate_invoice_compliance', [
                'uuid' => $uuid,
                'status' => 'success',
                'response' => $response,
            ]);

            return $response;
        } catch (ZatcaApiException $e) {
            $errorDetails = [
                'uuid' => $uuid,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            
            if (method_exists($e, 'getContext')) {
                $context = $e->getContext();
                if (!empty($context)) {
                    $errorDetails['context'] = $context;
                    if (isset($context['response'])) {
                        $errorDetails['api_response'] = $context['response'];
                        Log::error('ZATCA API Error Response', [
                            'response' => $context['response'],
                            'uuid' => $uuid,
                        ]);
                    }
                    if (isset($context['options'])) {
                        Log::debug('ZATCA API Request Details', [
                            'endpoint' => $context['endpoint'] ?? 'unknown',
                            'payload_keys' => array_keys($context['options']['json'] ?? []),
                        ]);
                    }
                }
            }
            
            $this->logApiCall('validate_invoice_compliance', $errorDetails);
            throw $e;
        } catch (\Exception $e) {
            $this->logApiCall('validate_invoice_compliance', [
                'uuid' => $uuid,
                'status' => 'error',
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
            ]);
            throw $e;
        }
    }

    public function requestProductionCertificate(string $complianceRequestId): array
    {
        try {
            $certificateData = $this->signingService->loadCertificateData();
            
            $result = $this->client->requestProductionCertificate(
                $certificateData['certificate'],
                $certificateData['secret'],
                $complianceRequestId
            );

            $this->logApiCall('request_production_certificate', [
                'compliance_request_id' => $complianceRequestId,
                'status' => 'success',
                'request_id' => $result->getRequestId(),
            ]);

            return [
                'certificate' => $result->getCertificate(),
                'secret' => $result->getSecret(),
                'request_id' => $result->getRequestId(),
            ];
        } catch (ZatcaApiException $e) {
            $this->logApiCall('request_production_certificate', [
                'compliance_request_id' => $complianceRequestId,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function logApiCall(string $endpoint, array $data): void
    {
        try {
            ZatcaLog::create([
                'endpoint' => $endpoint,
                'request_data' => $data,
                'response_data' => $data['response'] ?? null,
                'status' => $data['status'] ?? 'unknown',
                'error_message' => $data['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ZATCA API call to database', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'data' => $data,
            ]);
        }
    }
}

