<?php

namespace App\Services;

use Saleh7\Zatca\Helpers\Certificate;
use Saleh7\Zatca\InvoiceSigner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ZatcaSigningService
{
    protected string $signedInvoicesPath;
    protected string $qrCodesPath;

    public function __construct()
    {
        $this->signedInvoicesPath = config('zatca.paths.invoices_signed');
        $this->qrCodesPath = config('zatca.paths.qr_codes');
    }

    public function signInvoice(string $xmlInvoice, string $filename): array
    {
        try {
            $certificateData = $this->loadCertificateData();
            
            $certificate = new Certificate(
                $certificateData['certificate'],
                $certificateData['private_key'],
                $certificateData['secret']
            );

            $signedInvoice = InvoiceSigner::signInvoice($xmlInvoice, $certificate);
            $signedXml = $signedInvoice->getXML();
            $hash = $signedInvoice->getHash();
            $qrCode = $signedInvoice->getQRCode();

            $signedPath = $this->signedInvoicesPath . '/' . $filename;
            Storage::put($signedPath, $signedXml);

            $qrFilename = str_replace('.xml', '.txt', $filename);
            $qrPath = $this->qrCodesPath . '/' . $qrFilename;
            Storage::put($qrPath, $qrCode);

            Log::info('Invoice signed successfully', [
                'filename' => $filename,
                'signed_path' => $signedPath,
                'qr_path' => $qrPath,
            ]);

            return [
                'signed_xml' => $signedXml,
                'qr_code' => $qrCode,
                'hash' => $hash,
                'signed_path' => $signedPath,
                'qr_path' => $qrPath,
            ];
        } catch (\Exception $e) {
            Log::error('Invoice signing failed', [
                'error' => $e->getMessage(),
                'filename' => $filename,
            ]);
            throw $e;
        }
    }

    public function extractHashFromSignedXml(string $signedXml): string
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($signedXml);
            
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
            
            $references = $xpath->query('//ds:Reference[@URI="" and not(@Type)]/ds:DigestValue');
            if ($references->length > 0) {
                $hash = trim($references->item(0)->nodeValue);
                if (!empty($hash)) {
                    return $hash;
                }
            }
            
            $referencesById = $xpath->query('//ds:Reference[@Id="invoiceSignedData"]/ds:DigestValue');
            if ($referencesById->length > 0) {
                $hash = trim($referencesById->item(0)->nodeValue);
                if (!empty($hash)) {
                    return $hash;
                }
            }
            
            $referencesByUri = $xpath->query('//ds:Reference[@URI=""]/ds:DigestValue');
            if ($referencesByUri->length > 0) {
                foreach ($referencesByUri as $ref) {
                    $parent = $ref->parentNode;
                    if ($parent instanceof \DOMElement && !$parent->hasAttribute('Type')) {
                        $hash = trim($ref->nodeValue);
                        if (!empty($hash)) {
                            return $hash;
                        }
                    }
                }
            }
            
            $allReferences = $xpath->query('//ds:Reference[not(@Type)]/ds:DigestValue');
            if ($allReferences->length > 0) {
                $hash = trim($allReferences->item(0)->nodeValue);
                if (!empty($hash)) {
                    return $hash;
                }
            }
            
            $digestValues = $xpath->query('//ds:DigestValue');
            if ($digestValues->length > 0) {
                foreach ($digestValues as $digestValue) {
                    $parent = $digestValue->parentNode;
                    if ($parent instanceof \DOMElement && $parent->nodeName === 'ds:Reference' && !$parent->hasAttribute('Type')) {
                        $hash = trim($digestValue->nodeValue);
                        if (!empty($hash) && strlen($hash) > 20) {
                            return $hash;
                        }
                    }
                }
            }
            
            $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            
            $clonedDom = clone $dom;
            $clonedXpath = new \DOMXPath($clonedDom);
            $clonedXpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            $clonedXpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            
            $extensions = $clonedXpath->query('//ext:UBLExtensions');
            foreach ($extensions as $ext) {
                $ext->parentNode->removeChild($ext);
            }
            
            $signatures = $clonedXpath->query('//cac:Signature');
            foreach ($signatures as $sig) {
                $sig->parentNode->removeChild($sig);
            }
            
            $qrRefs = $clonedXpath->query("//cac:AdditionalDocumentReference[cbc:ID='QR']");
            foreach ($qrRefs as $qr) {
                $qr->parentNode->removeChild($qr);
            }
            
            $unsignedXml = $clonedDom->C14N(false, false);
            $hashBinary = hash('sha256', $unsignedXml, true);
            return base64_encode($hashBinary);
            
        } catch (\Exception $e) {
            Log::warning('Failed to extract hash from signed XML', ['error' => $e->getMessage()]);
            $hashBinary = hash('sha256', $signedXml, true);
            return base64_encode($hashBinary);
        }
    }

    public function loadCertificateData(): array
    {
        $certificateService = new ZatcaCertificateService();
        $paths = $certificateService->getCertificatePaths();

        if (!file_exists($paths['certificate_data'])) {
            throw new \Exception('Certificate data file not found. Please request compliance certificate first.');
        }

        $jsonData = json_decode(file_get_contents($paths['certificate_data']), true);
        
        $privateKey = file_get_contents($paths['private_key']);
        $cleanPrivateKey = trim(str_replace(
            ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"],
            "",
            $privateKey
        ));

        $certificate = $jsonData['certificate'];
        
        $decoded = base64_decode($certificate, true);
        if ($decoded !== false && strlen($decoded) > 50) {
            $certificate = $decoded;
        }
        
        if (strlen($certificate) < 50) {
            throw new \Exception('Certificate appears to be invalid or corrupted. Please request a new compliance certificate.');
        }

        return [
            'certificate' => $certificate,
            'private_key' => $cleanPrivateKey,
            'secret' => $jsonData['secret'],
            'request_id' => $jsonData['request_id'] ?? $jsonData['requestId'] ?? null,
        ];
    }
}

