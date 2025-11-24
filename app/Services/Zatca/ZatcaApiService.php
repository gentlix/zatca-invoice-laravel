<?php

namespace App\Services\Zatca;

use Illuminate\Support\Facades\Http;
use App\Models\ZatcaLog;
use Illuminate\Support\Facades\Log;

class ZatcaApiService
{
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $environment;

    public function __construct()
    {
        $this->environment = config('zatca.environment', 'sandbox');
        $this->baseUrl = $this->environment === 'production' 
            ? config('zatca.production_url')
            : config('zatca.sandbox_url');
        $this->clientId = config('zatca.client_id');
        $this->clientSecret = config('zatca.client_secret');
    }

    /**
     * Get OAuth Token from ZATCA
     */
    public function getAccessToken(): ?string
    {
        try {
            // Note: ZATCA API token endpoint may vary - adjust based on actual API documentation
            $response = Http::asForm()->post($this->baseUrl . '/compliance/invoices/issue', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? $data['token'] ?? null;
            }

            Log::error('ZATCA Token Error', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('ZATCA Token Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Submit invoice to ZATCA Compliance API
     */
    public function submitInvoice(string $signedXml, string $uuid, string $invoiceNumber = null): array
    {
        try {
            $token = $this->getAccessToken();
            
            if (!$token) {
                $this->logSubmission($uuid, $invoiceNumber, $signedXml, 'Failed to get access token', 401, ['error' => 'Authentication failed']);
                
                return [
                    'success' => false,
                    'message' => 'Failed to get access token from ZATCA',
                    'status_code' => 401
                ];
            }

            // Submit invoice to ZATCA
            // Note: Adjust endpoint and headers based on actual ZATCA API documentation
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/xml',
            ])->withBody($signedXml, 'application/xml')
            ->timeout(30)
            ->post($this->baseUrl . '/compliance/invoices/issue');

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json() ?? ['raw_response' => $responseBody];

            // Log to database
            $this->logSubmission($uuid, $invoiceNumber, $signedXml, $responseBody, $statusCode, $responseData);

            $isSuccess = $response->successful();

            return [
                'success' => $isSuccess,
                'status_code' => $statusCode,
                'response' => $responseData,
                'message' => $isSuccess 
                    ? 'Invoice submitted successfully to ZATCA' 
                    : ($responseData['message'] ?? 'Invoice submission failed')
            ];

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logSubmission($uuid, $invoiceNumber, $signedXml, $errorMessage, 500, ['error' => $errorMessage]);
            
            Log::error('ZATCA Submission Exception', [
                'uuid' => $uuid,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Exception: ' . $errorMessage
            ];
        }
    }

    /**
     * Log submission to database
     */
    private function logSubmission(string $uuid, ?string $invoiceNumber, string $requestXml, string $response, int $statusCode, array $responseData): void
    {
        try {
            ZatcaLog::create([
                'invoice_uuid' => $uuid,
                'invoice_number' => $invoiceNumber,
                'request_xml' => $requestXml,
                'response' => $responseData,
                'status_code' => $statusCode,
                'status' => $statusCode >= 200 && $statusCode < 300 ? 'success' : 'error',
                'error_message' => $statusCode >= 400 ? ($responseData['message'] ?? $responseData['error'] ?? $response) : null
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ZATCA submission', [
                'error' => $e->getMessage(),
                'uuid' => $uuid
            ]);
        }
    }

    /**
     * Validate invoice (pre-submission validation)
     */
    public function validateInvoice(string $signedXml): array
    {
        try {
            $token = $this->getAccessToken();
            
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Failed to get access token'
                ];
            }

            // Note: Adjust endpoint based on actual ZATCA API
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/xml',
            ])->withBody($signedXml, 'application/xml')
            ->post($this->baseUrl . '/compliance/invoices/validate');

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->json() ?? ['raw' => $response->body()]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

