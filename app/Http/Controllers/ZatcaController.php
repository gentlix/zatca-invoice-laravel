<?php

namespace App\Http\Controllers;

use App\Data\SampleInvoices;
use App\Services\Zatca\CertificateService;
use App\Services\Zatca\XmlService;
use App\Services\Zatca\QrCodeService;
use App\Services\Zatca\ZatcaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ZatcaController extends Controller
{
    protected $certificateService;
    protected $xmlService;
    protected $qrCodeService;
    protected $apiService;

    public function __construct(
        CertificateService $certificateService,
        XmlService $xmlService,
        QrCodeService $qrCodeService,
        ZatcaApiService $apiService
    ) {
        $this->certificateService = $certificateService;
        $this->xmlService = $xmlService;
        $this->qrCodeService = $qrCodeService;
        $this->apiService = $apiService;
    }

    /**
     * Generate CSR (Certificate Signing Request)
     */
    public function generateCSR(Request $request)
    {
        $sellerInfo = [
            'name' => $request->input('name', 'Test Company Ltd'),
            'vat_registration_number' => $request->input('vat_number', '123456789100003'),
            'city' => $request->input('city', 'Riyadh'),
            'state' => $request->input('state', 'Riyadh'),
            'email' => $request->input('email', 'info@example.com'),
            'unit' => $request->input('unit', 'IT'),
        ];

        $result = $this->certificateService->generateCSR($sellerInfo);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'CSR generated successfully',
            'csr_path' => $result['csr_path'],
            'private_key_path' => $result['private_key_path'],
            'csr' => $result['csr'],
            'instructions' => [
                '1. Copy the CSR content above',
                '2. Submit it to ZATCA portal to get your certificate',
                '3. Save the certificate to: ' . config('zatca.certificate_path'),
                '4. The private key is already saved at: ' . $result['private_key_path']
            ]
        ]);
    }

    /**
     * Save certificate from ZATCA
     */
    public function saveCertificate(Request $request)
    {
        $certificateContent = $request->input('certificate');
        
        if (empty($certificateContent)) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate content is required. Please provide the certificate in PEM format.'
            ], 400);
        }

        // Validate that it looks like a PEM certificate
        if (strpos($certificateContent, '-----BEGIN CERTIFICATE-----') === false) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid certificate format. Certificate must be in PEM format (should start with -----BEGIN CERTIFICATE-----)'
            ], 400);
        }

        $result = $this->certificateService->saveCertificate($certificateContent);
        
        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificate saved successfully',
            'certificate_path' => $result['certificate_path'],
            'instructions' => [
                'Certificate has been saved and is ready to use.',
                'You can now process invoices using this certificate.',
                'For production, ensure this is the official certificate from ZATCA portal.'
            ]
        ]);
    }

    /**
     * Process Invoice 1
     */
    public function processInvoice1()
    {
        return $this->processInvoice(SampleInvoices::getInvoice1(), 'invoice_1');
    }

    /**
     * Process Invoice 2
     */
    public function processInvoice2()
    {
        return $this->processInvoice(SampleInvoices::getInvoice2(), 'invoice_2');
    }

    /**
     * Process invoice (generate XML, sign, QR, submit)
     */
    private function processInvoice(array $invoiceData, string $invoiceName)
    {
        try {
            // Check if certificate exists
            if (!$this->certificateService->hasCertificate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate or private key not found. Please generate CSR and obtain certificate first.',
                    'endpoint' => '/zatca/generate-csr'
                ], 400);
            }

            // 1. Generate unsigned XML
            $xmlResult = $this->xmlService->generateUnsignedXml($invoiceData);
            
            if (!$xmlResult['success']) {
                return response()->json($xmlResult, 400);
            }

            $unsignedXml = $xmlResult['xml'];
            $uuid = $xmlResult['uuid'];

            // Create invoices directory if it doesn't exist
            if (!Storage::disk('local')->exists('invoices')) {
                Storage::disk('local')->makeDirectory('invoices');
            }

            // Save unsigned XML
            Storage::disk('local')->put("invoices/{$invoiceName}_unsigned.xml", $unsignedXml);

            // 2. Sign XML
            $certificatePath = config('zatca.certificate_path');
            $privateKeyPath = config('zatca.private_key_path');

            $signResult = $this->xmlService->signXml($unsignedXml, $certificatePath, $privateKeyPath);
            
            if (!$signResult['success']) {
                return response()->json($signResult, 400);
            }

            $signedXml = $signResult['xml'];
            
            // Save signed XML
            Storage::disk('local')->put("invoices/{$invoiceName}_signed.xml", $signedXml);

            // 3. Generate QR Code
            $signedInvoiceHash = base64_encode(hash('sha256', $signedXml, true));
            $qrResult = $this->qrCodeService->generateQrCode($invoiceData, $uuid, $signedInvoiceHash);
            
            if (!$qrResult['success']) {
                return response()->json($qrResult, 400);
            }

            // 4. Submit to ZATCA
            $submissionResult = $this->apiService->submitInvoice($signedXml, $uuid, $invoiceData['invoice_number']);

            return response()->json([
                'success' => true,
                'invoice_number' => $invoiceData['invoice_number'],
                'invoice_uuid' => $uuid,
                'files' => [
                    'unsigned_xml' => storage_path("app/invoices/{$invoiceName}_unsigned.xml"),
                    'signed_xml' => storage_path("app/invoices/{$invoiceName}_signed.xml"),
                    'qr_code' => $qrResult['qr_code_path'],
                ],
                'zatca_submission' => $submissionResult,
                'message' => 'Invoice processed successfully. Check zatca_submission for ZATCA API response.'
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice Processing Error', [
                'invoice' => $invoiceName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing invoice: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * View logs
     */
    public function viewLogs(Request $request)
    {
        $query = \App\Models\ZatcaLog::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('invoice_uuid')) {
            $query->where('invoice_uuid', $request->input('invoice_uuid'));
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($logs);
    }

    /**
     * Get log details
     */
    public function getLog($id)
    {
        $log = \App\Models\ZatcaLog::findOrFail($id);
        
        return response()->json($log);
    }
}

