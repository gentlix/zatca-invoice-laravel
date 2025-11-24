<?php

namespace App\Services\Zatca;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QrCodeService
{
    /**
     * Generate QR Code TLV format for ZATCA
     */
    public function generateQrCode(array $invoiceData, string $uuid, string $signedInvoiceHash): array
    {
        try {
            // TLV encoding for ZATCA QR Code
            $tlvData = $this->encodeTLV($invoiceData, $uuid, $signedInvoiceHash);
            
            // Generate QR code
            $qrCode = QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->generate($tlvData);
            
            // Create qr_codes directory if it doesn't exist
            if (!Storage::disk('local')->exists('qr_codes')) {
                Storage::disk('local')->makeDirectory('qr_codes');
            }
            
            // Save QR code
            $fileName = 'qr_' . $uuid . '.png';
            $path = 'qr_codes/' . $fileName;
            Storage::disk('local')->put($path, $qrCode);
            
            return [
                'success' => true,
                'qr_code_path' => storage_path('app/' . $path),
                'qr_code_url' => $path,
                'tlv_data' => $tlvData
            ];

        } catch (\Exception $e) {
            Log::error('QR Code Generation Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Encode TLV (Tag-Length-Value) format for ZATCA QR Code
     */
    private function encodeTLV(array $invoiceData, string $uuid, string $hash): string
    {
        $tlv = '';
        
        // Tag 1: Seller Name
        $sellerName = $invoiceData['seller']['name'];
        $tlv .= $this->encodeTLVField(1, $sellerName);
        
        // Tag 2: VAT Registration Number
        $vatNumber = $invoiceData['seller']['vat_registration_number'];
        $tlv .= $this->encodeTLVField(2, $vatNumber);
        
        // Tag 3: Invoice Date/Time
        $invoiceDateTime = $invoiceData['invoice_date'] . 'T' . $invoiceData['invoice_time'];
        $tlv .= $this->encodeTLVField(3, $invoiceDateTime);
        
        // Tag 4: Invoice Total
        $invoiceTotal = number_format($invoiceData['totals']['total'], 2, '.', '');
        $tlv .= $this->encodeTLVField(4, $invoiceTotal);
        
        // Tag 5: VAT Total
        $vatTotal = number_format($invoiceData['totals']['vat_total'], 2, '.', '');
        $tlv .= $this->encodeTLVField(5, $vatTotal);
        
        // Tag 6: Invoice Hash (Base64 encoded)
        $tlv .= $this->encodeTLVField(6, $hash);
        
        return base64_encode($tlv);
    }

    /**
     * Encode a single TLV field
     */
    private function encodeTLVField(int $tag, string $value): string
    {
        $valueBytes = $value;
        $length = strlen($valueBytes);
        
        // Ensure length fits in one byte (max 255)
        if ($length > 255) {
            throw new \Exception("TLV field value too long: {$length} bytes");
        }
        
        return chr($tag) . chr($length) . $valueBytes;
    }
}

