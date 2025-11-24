<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Data\SampleInvoices;
use App\Services\Zatca\CertificateService;
use App\Services\Zatca\XmlService;
use App\Services\Zatca\QrCodeService;
use App\Services\Zatca\ZatcaApiService;
use Illuminate\Support\Facades\Storage;

class TestPageController extends Controller
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
     * Show test page
     */
    public function index()
    {
        return view('test-page');
    }

    /**
     * Test Generate CSR endpoint
     */
    public function testGenerateCSR(Request $request)
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

        return response()->json($result);
    }

    /**
     * Test Process Invoice 1
     */
    public function testProcessInvoice1()
    {
        return $this->processInvoice(SampleInvoices::getInvoice1(), 'invoice_1');
    }

    /**
     * Test Process Invoice 2
     */
    public function testProcessInvoice2()
    {
        return $this->processInvoice(SampleInvoices::getInvoice2(), 'invoice_2');
    }

    /**
     * Process invoice
     */
    private function processInvoice(array $invoiceData, string $invoiceName)
    {
        try {
            if (!$this->certificateService->hasCertificate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate or private key not found. Please generate CSR first.'
                ], 400);
            }

            // Generate unsigned XML
            $xmlResult = $this->xmlService->generateUnsignedXml($invoiceData);
            if (!$xmlResult['success']) {
                return response()->json($xmlResult, 400);
            }

            $unsignedXml = $xmlResult['xml'];
            $uuid = $xmlResult['uuid'];

            // Save unsigned XML
            if (!Storage::disk('local')->exists('invoices')) {
                Storage::disk('local')->makeDirectory('invoices');
            }
            Storage::disk('local')->put("invoices/{$invoiceName}_unsigned.xml", $unsignedXml);

            // Sign XML
            $certificatePath = config('zatca.certificate_path');
            $privateKeyPath = config('zatca.private_key_path');
            $signResult = $this->xmlService->signXml($unsignedXml, $certificatePath, $privateKeyPath);
            
            if (!$signResult['success']) {
                return response()->json($signResult, 400);
            }

            $signedXml = $signResult['xml'];
            Storage::disk('local')->put("invoices/{$invoiceName}_signed.xml", $signedXml);

            // Generate QR Code
            $signedInvoiceHash = base64_encode(hash('sha256', $signedXml, true));
            $qrResult = $this->qrCodeService->generateQrCode($invoiceData, $uuid, $signedInvoiceHash);
            
            if (!$qrResult['success']) {
                return response()->json($qrResult, 400);
            }

            // Submit to ZATCA
            $submissionResult = $this->apiService->submitInvoice($signedXml, $uuid, $invoiceData['invoice_number']);

            return response()->json([
                'success' => true,
                'invoice_number' => $invoiceData['invoice_number'],
                'invoice_uuid' => $uuid,
                'files' => [
                    'unsigned_xml' => "invoices/{$invoiceName}_unsigned.xml",
                    'signed_xml' => "invoices/{$invoiceName}_signed.xml",
                    'qr_code' => $qrResult['qr_code_url'] ?? null,
                ],
                'zatca_submission' => $submissionResult
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get logs
     */
    public function getLogs(Request $request)
    {
        $query = \App\Models\ZatcaLog::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $logs = $query->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json($logs);
    }
}

