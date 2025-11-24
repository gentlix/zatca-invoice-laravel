<?php

namespace App\Services\Zatca;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;

class XmlService
{
    /**
     * Generate unsigned XML invoice
     */
    public function generateUnsignedXml(array $invoiceData): array
    {
        try {
            $uuid = Uuid::uuid4()->toString();
            
            $xml = new \DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;

            $invoice = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', 'Invoice');
            $invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
            $invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            
            $xml->appendChild($invoice);

            // Extensions
            $extensions = $xml->createElement('ext:UBLExtensions');
            $invoice->appendChild($extensions);

            // Invoice ID
            $invoiceId = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:ID', $invoiceData['invoice_number']);
            $invoice->appendChild($invoiceId);

            // UUID
            $uuidElement = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:UUID', $uuid);
            $invoice->appendChild($uuidElement);

            // Issue Date
            $issueDate = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:IssueDate', $invoiceData['invoice_date']);
            $invoice->appendChild($issueDate);

            // Issue Time
            $issueTime = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:IssueTime', $invoiceData['invoice_time']);
            $invoice->appendChild($issueTime);

            // Invoice Type Code
            $invoiceTypeCode = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:InvoiceTypeCode', $invoiceData['invoice_type_code']);
            $invoice->appendChild($invoiceTypeCode);

            // Document Currency Code
            $currencyCode = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:DocumentCurrencyCode', $invoiceData['currency']);
            $invoice->appendChild($currencyCode);

            // Accounting Supplier Party
            $supplierParty = $this->createSupplierParty($xml, $invoiceData['seller']);
            $invoice->appendChild($supplierParty);

            // Accounting Customer Party
            $customerParty = $this->createCustomerParty($xml, $invoiceData['buyer']);
            $invoice->appendChild($customerParty);

            // Invoice Lines
            foreach ($invoiceData['items'] as $index => $item) {
                $invoiceLine = $this->createInvoiceLine($xml, $item, $index + 1);
                $invoice->appendChild($invoiceLine);
            }

            // Legal Monetary Total
            $legalMonetaryTotal = $this->createLegalMonetaryTotal($xml, $invoiceData['totals']);
            $invoice->appendChild($legalMonetaryTotal);

            // Tax Total
            $invoiceTaxTotal = $this->createTaxTotal($xml, $invoiceData['totals']['vat_total']);
            $invoice->appendChild($invoiceTaxTotal);

            return [
                'success' => true,
                'xml' => $xml->saveXML(),
                'uuid' => $uuid
            ];

        } catch (\Exception $e) {
            Log::error('XML Generation Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create Supplier Party element
     */
    private function createSupplierParty(\DOMDocument $xml, array $seller): \DOMElement
    {
        $supplierParty = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:AccountingSupplierParty');
        $party = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:Party');
        
        $partyName = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:RegistrationName', $seller['name']);
        $party->appendChild($partyName);

        $partyTaxScheme = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:PartyTaxScheme');
        $companyId = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:CompanyID', $seller['vat_registration_number']);
        $partyTaxScheme->appendChild($companyId);
        $party->appendChild($partyTaxScheme);

        $postalAddress = $this->createPostalAddress($xml, $seller['address']);
        $party->appendChild($postalAddress);

        $supplierParty->appendChild($party);
        return $supplierParty;
    }

    /**
     * Create Customer Party element
     */
    private function createCustomerParty(\DOMDocument $xml, array $buyer): \DOMElement
    {
        $customerParty = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:AccountingCustomerParty');
        $party = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:Party');
        
        $customerName = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:RegistrationName', $buyer['name']);
        $party->appendChild($customerName);

        $customerTaxScheme = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:PartyTaxScheme');
        $customerCompanyId = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:CompanyID', $buyer['vat_registration_number']);
        $customerTaxScheme->appendChild($customerCompanyId);
        $party->appendChild($customerTaxScheme);

        $customerPostalAddress = $this->createPostalAddress($xml, $buyer['address']);
        $party->appendChild($customerPostalAddress);

        $customerParty->appendChild($party);
        return $customerParty;
    }

    /**
     * Create Postal Address element
     */
    private function createPostalAddress(\DOMDocument $xml, array $address): \DOMElement
    {
        $postalAddress = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:PostalAddress');
        
        $streetName = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:StreetName', $address['street']);
        $postalAddress->appendChild($streetName);
        
        $buildingNumber = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:BuildingNumber', $address['building_number']);
        $postalAddress->appendChild($buildingNumber);
        
        $cityName = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:CityName', $address['city']);
        $postalAddress->appendChild($cityName);
        
        $postalZone = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:PostalZone', $address['postal_code']);
        $postalAddress->appendChild($postalZone);
        
        $country = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:Country');
        $countryCode = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:IdentificationCode', $address['country']);
        $country->appendChild($countryCode);
        $postalAddress->appendChild($country);
        
        return $postalAddress;
    }

    /**
     * Create Invoice Line element
     */
    private function createInvoiceLine(\DOMDocument $xml, array $item, int $lineNumber): \DOMElement
    {
        $invoiceLine = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:InvoiceLine');
        
        $lineId = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:ID', (string)$lineNumber);
        $invoiceLine->appendChild($lineId);
        
        $invoicedQuantity = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:InvoicedQuantity', (string)$item['quantity']);
        $invoicedQuantity->setAttribute('unitCode', 'C62');
        $invoiceLine->appendChild($invoicedQuantity);
        
        $lineExtensionAmount = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:LineExtensionAmount', number_format($item['total'] - $item['tax_amount'], 2, '.', ''));
        $invoiceLine->appendChild($lineExtensionAmount);
        
        $itemElement = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:Item');
        $itemName = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:Name', $item['name']);
        $itemElement->appendChild($itemName);
        $invoiceLine->appendChild($itemElement);
        
        $price = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:Price');
        $priceAmount = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:PriceAmount', number_format($item['unit_price'], 2, '.', ''));
        $price->appendChild($priceAmount);
        $invoiceLine->appendChild($price);
        
        $taxTotal = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:TaxTotal');
        $taxAmount = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:TaxAmount', number_format($item['tax_amount'], 2, '.', ''));
        $taxTotal->appendChild($taxAmount);
        
        $taxSubtotal = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:TaxSubtotal');
        $taxableAmount = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:TaxableAmount', number_format($item['total'] - $item['tax_amount'], 2, '.', ''));
        $taxSubtotal->appendChild($taxableAmount);
        $taxAmountSubtotal = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:TaxAmount', number_format($item['tax_amount'], 2, '.', ''));
        $taxSubtotal->appendChild($taxAmountSubtotal);
        
        $taxCategory = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:TaxCategory');
        $taxScheme = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:TaxScheme');
        $taxSchemeId = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:ID', 'VAT');
        $taxScheme->appendChild($taxSchemeId);
        $taxCategory->appendChild($taxScheme);
        $taxSubtotal->appendChild($taxCategory);
        $taxTotal->appendChild($taxSubtotal);
        $invoiceLine->appendChild($taxTotal);
        
        return $invoiceLine;
    }

    /**
     * Create Legal Monetary Total element
     */
    private function createLegalMonetaryTotal(\DOMDocument $xml, array $totals): \DOMElement
    {
        $legalMonetaryTotal = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:LegalMonetaryTotal');
        
        $lineExtensionTotal = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:LineExtensionAmount', number_format($totals['subtotal'], 2, '.', ''));
        $legalMonetaryTotal->appendChild($lineExtensionTotal);
        
        $taxExclusiveAmount = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:TaxExclusiveAmount', number_format($totals['subtotal'], 2, '.', ''));
        $legalMonetaryTotal->appendChild($taxExclusiveAmount);
        
        $taxInclusiveAmount = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:TaxInclusiveAmount', number_format($totals['total'], 2, '.', ''));
        $legalMonetaryTotal->appendChild($taxInclusiveAmount);
        
        $payableAmount = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:PayableAmount', number_format($totals['total'], 2, '.', ''));
        $legalMonetaryTotal->appendChild($payableAmount);
        
        return $legalMonetaryTotal;
    }

    /**
     * Create Tax Total element
     */
    private function createTaxTotal(\DOMDocument $xml, float $vatTotal): \DOMElement
    {
        $invoiceTaxTotal = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'cac:TaxTotal');
        $invoiceTaxAmount = $xml->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'cbc:TaxAmount', number_format($vatTotal, 2, '.', ''));
        $invoiceTaxTotal->appendChild($invoiceTaxAmount);
        
        return $invoiceTaxTotal;
    }

    /**
     * Sign XML invoice
     */
    public function signXml(string $unsignedXml, string $certificatePath, string $privateKeyPath): array
    {
        try {
            if (!file_exists($certificatePath) || !file_exists($privateKeyPath)) {
                throw new \Exception('Certificate or private key file not found');
            }

            $xml = new \DOMDocument();
            $xml->loadXML($unsignedXml);

            $certificate = file_get_contents($certificatePath);
            $privateKey = file_get_contents($privateKeyPath);

            // Read certificate
            $cert = openssl_x509_read($certificate);
            if (!$cert) {
                throw new \Exception('Failed to read certificate: ' . openssl_error_string());
            }

            // Read private key
            $privateKeyResource = openssl_pkey_get_private($privateKey);
            if (!$privateKeyResource) {
                throw new \Exception('Failed to read private key: ' . openssl_error_string());
            }

            // Extract certificate data
            openssl_x509_export($cert, $certPem);
            $certLines = explode("\n", $certPem);
            $certBase64 = '';
            foreach ($certLines as $line) {
                if (strpos($line, '-----') === false) {
                    $certBase64 .= trim($line);
                }
            }

            // Create signature element
            $xpath = new \DOMXPath($xml);
            $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

            $extensions = $xpath->query('//ext:UBLExtensions')->item(0);
            if (!$extensions) {
                throw new \Exception('UBLExtensions not found in XML');
            }

            // Create signature structure
            $signature = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Signature');
            $signature->setAttribute('Id', 'signature');

            // SignedInfo
            $signedInfo = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignedInfo');
            
            $canonicalizationMethod = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:CanonicalizationMethod');
            $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
            $signedInfo->appendChild($canonicalizationMethod);
            
            $signatureMethod = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignatureMethod');
            $signatureMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
            $signedInfo->appendChild($signatureMethod);
            
            $reference = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Reference');
            $reference->setAttribute('URI', '');
            
            $transforms = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Transforms');
            $transform1 = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Transform');
            $transform1->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
            $transforms->appendChild($transform1);
            $transform2 = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Transform');
            $transform2->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
            $transforms->appendChild($transform2);
            $reference->appendChild($transforms);
            
            $digestMethod = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:DigestMethod');
            $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
            $reference->appendChild($digestMethod);
            
            $digestValue = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:DigestValue', '');
            $reference->appendChild($digestValue);
            
            $signedInfo->appendChild($reference);
            $signature->appendChild($signedInfo);
            
            // SignatureValue
            $signatureValue = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignatureValue', '');
            $signature->appendChild($signatureValue);
            
            // KeyInfo
            $keyInfo = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo');
            $x509Data = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Data');
            $x509Certificate = $xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Certificate', '');
            $x509Data->appendChild($x509Certificate);
            $keyInfo->appendChild($x509Data);
            $signature->appendChild($keyInfo);

            $extensions->appendChild($signature);

            // Calculate digest and signature
            $tempXml = new \DOMDocument();
            $tempXml->loadXML($xml->saveXML());
            $tempXpath = new \DOMXPath($tempXml);
            $tempXpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
            $tempSignature = $tempXpath->query('//ds:Signature')->item(0);
            if ($tempSignature) {
                $tempSignature->parentNode->removeChild($tempSignature);
            }
            
            $dataToSign = $tempXml->C14N(true, false);
            $digest = base64_encode(hash('sha256', $dataToSign, true));
            
            $signatureResult = '';
            openssl_sign($dataToSign, $signatureResult, $privateKeyResource, OPENSSL_ALGO_SHA256);
            $signatureBase64 = base64_encode($signatureResult);
            
            // Update signature values
            $finalDigestValue = $xpath->query('//ds:DigestValue')->item(0);
            if ($finalDigestValue) {
                $finalDigestValue->nodeValue = $digest;
            }
            
            $finalSignatureValue = $xpath->query('//ds:SignatureValue')->item(0);
            if ($finalSignatureValue) {
                $finalSignatureValue->nodeValue = $signatureBase64;
            }
            
            $finalX509Certificate = $xpath->query('//ds:X509Certificate')->item(0);
            if ($finalX509Certificate) {
                $finalX509Certificate->nodeValue = $certBase64;
            }

            return [
                'success' => true,
                'xml' => $xml->saveXML()
            ];

        } catch (\Exception $e) {
            Log::error('XML Signing Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

