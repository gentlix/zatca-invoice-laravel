<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Saleh7\Zatca\Helpers\Certificate;
use Saleh7\Zatca\Helpers\InvoiceExtension;
use Saleh7\Zatca\InvoiceSigner;

class QrCodeService
{
    /**
     * Generate and save QR code from signed invoice
     */
    public function generateAndSave(string $signedInvoiceXml, Certificate $certificate, string $invoiceId): string
    {
        // Extract QR code from signed invoice
        $invoiceExtension = InvoiceExtension::fromString($signedInvoiceXml);
        
        // Get QR code from the signed invoice (it's already generated during signing)
        $signer = InvoiceSigner::signInvoice($signedInvoiceXml, $certificate);
        $qrCodeBase64 = $signer->getQRCode();
        
        // Decode base64 to get the TLV string
        $tlvString = base64_decode($qrCodeBase64);
        
        // Generate QR code image from TLV string
        return $this->generateQrCodeImage($tlvString, $invoiceId);
    }

    /**
     * Extract QR code from signed invoice XML and save it as image
     */
    public function extractAndSave(string $signedInvoiceXml, string $invoiceId): ?string
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($signedInvoiceXml);
            
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            
            // Find QR code in AdditionalDocumentReference with ID="QR"
            $qrNodes = $xpath->query("//cac:AdditionalDocumentReference[cbc:ID='QR']/cac:Attachment/cbc:EmbeddedDocumentBinaryObject");
            
            if ($qrNodes->length > 0) {
                $qrCodeBase64 = $qrNodes->item(0)->nodeValue;
                
                // Decode base64 to get the TLV string
                $tlvString = base64_decode($qrCodeBase64);
                
                // Generate QR code image from TLV string
                return $this->generateQrCodeImage($tlvString, $invoiceId);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to extract QR code: '.$e->getMessage());
        }
        
        return null;
    }

    /**
     * Generate QR code image from base64 encoded TLV string
     * Returns PNG if possible, otherwise SVG
     */
    private function generateQrCodeImage(string $qrCodeBase64, string $invoiceId): string
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('zatca/qr-codes');
        
        $qrCodePath = "zatca/qr-codes/{$invoiceId}.png";
        $absolutePath = $disk->path($qrCodePath);
        
        // Try PNG first
        try {
            QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->errorCorrection('H')
                ->encoding('UTF-8')
                ->generate($qrCodeBase64, $absolutePath);
            
            if (file_exists($absolutePath) && filesize($absolutePath) > 0) {
                return $absolutePath;
            }
        } catch (\Exception $e) {
            // PNG failed, use SVG
        }
        
        // Fallback to SVG
        $svgPath = str_replace('.png', '.svg', $absolutePath);
        QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->errorCorrection('H')
            ->encoding('UTF-8')
            ->generate($qrCodeBase64, $svgPath);
        
        return $svgPath;
    }
}

