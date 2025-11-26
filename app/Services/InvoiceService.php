<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Saleh7\Zatca\AdditionalDocumentReference;
use Saleh7\Zatca\Address;
use Saleh7\Zatca\Attachment;
use Saleh7\Zatca\ClassifiedTaxCategory;
use Saleh7\Zatca\Delivery;
use Saleh7\Zatca\ExtensionContent;
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\Invoice;
use Saleh7\Zatca\InvoiceLine;
use Saleh7\Zatca\InvoiceType;
use Saleh7\Zatca\Item;
use Saleh7\Zatca\LegalEntity;
use Saleh7\Zatca\LegalMonetaryTotal;
use Saleh7\Zatca\Party;
use Saleh7\Zatca\PartyTaxScheme;
use Saleh7\Zatca\PaymentMeans;
use Saleh7\Zatca\Price;
use Saleh7\Zatca\Signature;
use Saleh7\Zatca\SignatureInformation;
use Saleh7\Zatca\TaxCategory;
use Saleh7\Zatca\TaxScheme;
use Saleh7\Zatca\TaxSubTotal;
use Saleh7\Zatca\TaxTotal;
use Saleh7\Zatca\UBLDocumentSignatures;
use Saleh7\Zatca\UBLExtension;
use Saleh7\Zatca\UBLExtensions;
use Saleh7\Zatca\UnitCode;

class InvoiceService
{
    /**
     * Generate invoice XML from array data
     */
    public function generateFromArray(array $invoiceData): string
    {
        $issueDate = isset($invoiceData['issue_date']) 
            ? Carbon::parse($invoiceData['issue_date'])->utc() 
            : now()->utc();
        
        $uuid = $invoiceData['uuid'] ?? Str::uuid()->toString();

        // === 1. UBL Signature Extensions ===
        $signatureInfo = (new SignatureInformation)
            ->setReferencedSignatureID("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setID('urn:oasis:names:specification:ubl:signature:1');

        $ublDocSignatures = (new UBLDocumentSignatures)
            ->setSignatureInformation($signatureInfo);

        $extensionContent = (new ExtensionContent)
            ->setUBLDocumentSignatures($ublDocSignatures);

        $ublExtension = (new UBLExtension)
            ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
            ->setExtensionContent($extensionContent);

        $ublExtensions = (new UBLExtensions)
            ->setUBLExtensions([$ublExtension]);

        // === 2. Signature Section ===
        $signature = (new Signature)
            ->setId("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setSignatureMethod("urn:oasis:names:specification:ubl:dsig:enveloped:xades");

        // === 3. Invoice Type ===
        $invoiceType = (new InvoiceType())
            ->setInvoice($invoiceData['invoice_type'] ?? 'simplified')
            ->setInvoiceType('invoice')
            ->setIsThirdParty($invoiceData['is_third_party'] ?? false);

        // === 4. Attachments ===
        $attachment = (new Attachment())
            ->setBase64Content(
                $invoiceData['pih'] ?? 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
                'base64',
                'text/plain'
            );

        // === 5. Additional Document References ===
        $additionalDocs = [
            (new AdditionalDocumentReference())->setId('ICV')->setUUID($invoiceData['icv'] ?? "10"),
            (new AdditionalDocumentReference())->setId('PIH')->setAttachment($attachment),
            (new AdditionalDocumentReference())->setId('QR'),
        ];

        // === 6. Supplier / Customer Info ===
        $taxScheme = (new TaxScheme())->setId("VAT");

        $supplierData = $invoiceData['supplier'] ?? [];
        $customerData = $invoiceData['customer'] ?? [];

        $partyTaxSchemeSupplier = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId($supplierData['tax_id'] ?? Config::get('zatca.organization.identifier'));

        $partyTaxSchemeCustomer = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId($customerData['tax_id'] ?? null);

        $addressSupplier = (new Address())
            ->setStreetName($supplierData['address']['street'] ?? Config::get('zatca.organization.address'))
            ->setBuildingNumber($supplierData['address']['building_number'] ?? Config::get('zatca.organization.building_number'))
            ->setCitySubdivisionName($supplierData['address']['city_subdivision'] ?? Config::get('zatca.organization.city_subdivision_name'))
            ->setCityName($supplierData['address']['city'] ?? Config::get('zatca.organization.city'))
            ->setPostalZone($supplierData['address']['postal_code'] ?? Config::get('zatca.organization.postal_code'))
            ->setCountry($supplierData['address']['country'] ?? Config::get('zatca.organization.country_code'));

        $addressCustomer = (new Address())
            ->setStreetName($customerData['address']['street'] ?? 'Salah Al-Din')
            ->setBuildingNumber($customerData['address']['building_number'] ?? "1111")
            ->setCitySubdivisionName($customerData['address']['city_subdivision'] ?? 'Al-Murooj')
            ->setCityName($customerData['address']['city'] ?? 'Riyadh')
            ->setPostalZone($customerData['address']['postal_code'] ?? '12222')
            ->setCountry($customerData['address']['country'] ?? 'SA');

        $legalEntitySupplier = (new LegalEntity())
            ->setRegistrationName($supplierData['name'] ?? Config::get('zatca.organization.name'));

        $legalEntityCustomer = (new LegalEntity())
            ->setRegistrationName($customerData['name'] ?? 'Customer Name');

        $supplierCompany = (new Party())
            ->setPartyIdentification($supplierData['registration_number'] ?? Config::get('zatca.organization.registration_number'))
            ->setPartyIdentificationId("CRN")
            ->setLegalEntity($legalEntitySupplier)
            ->setPartyTaxScheme($partyTaxSchemeSupplier)
            ->setPostalAddress($addressSupplier);

        $supplierCustomer = (new Party())
            ->setPartyIdentification($customerData['registration_number'] ?? "1234567890")
            ->setPartyIdentificationId("NAT")
            ->setLegalEntity($legalEntityCustomer)
            ->setPartyTaxScheme($partyTaxSchemeCustomer)
            ->setPostalAddress($addressCustomer);

        // === 7. Delivery / Payment ===
        $delivery = (new Delivery())
            ->setActualDeliveryDate($issueDate->format('Y-m-d'));

        $paymentMeans = (new PaymentMeans())
            ->setPaymentMeansCode($invoiceData['payment_means_code'] ?? "10");

        // === 8. Tax Structure ===
        $taxPercent = $invoiceData['tax_percent'] ?? 15;
        $taxCategory = (new TaxCategory())
            ->setPercent($taxPercent)
            ->setTaxScheme($taxScheme);

        $taxableAmount = $invoiceData['taxable_amount'] ?? 0;
        $taxAmount = $invoiceData['tax_amount'] ?? 0;

        $taxSubTotal = (new TaxSubTotal())
            ->setTaxableAmount($taxableAmount)
            ->setTaxAmount($taxAmount)
            ->setTaxCategory($taxCategory);

        $taxTotal = (new TaxTotal())
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount($taxAmount);

        // === 9. Totals ===
        $totals = $invoiceData['totals'] ?? [];
        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setLineExtensionAmount($totals['line_extension'] ?? $taxableAmount)
            ->setTaxExclusiveAmount($totals['tax_exclusive'] ?? $taxableAmount)
            ->setTaxInclusiveAmount($totals['tax_inclusive'] ?? ($taxableAmount + $taxAmount))
            ->setAllowanceTotalAmount($totals['allowance'] ?? 0)
            ->setPrepaidAmount($totals['prepaid'] ?? 0)
            ->setPayableAmount($totals['payable'] ?? ($taxableAmount + $taxAmount));

        // === 10. Line Items ===
        $invoiceLines = [];
        $lineItems = $invoiceData['line_items'] ?? [];

        foreach ($lineItems as $index => $item) {
            $classifiedTax = (new ClassifiedTaxCategory())
                ->setPercent($item['tax_percent'] ?? $taxPercent)
                ->setTaxScheme($taxScheme);

            $productItem = (new Item())
                ->setName($item['name'] ?? 'Product')
                ->setClassifiedTaxCategory($classifiedTax);

            $price = (new Price())
                ->setUnitCode(UnitCode::UNIT)
                ->setPriceAmount($item['price'] ?? 0);

            $lineTaxTotal = (new TaxTotal())
                ->setTaxAmount($item['tax_amount'] ?? 0)
                ->setRoundingAmount($item['total'] ?? 0);

            $invoiceLine = (new InvoiceLine())
                ->setUnitCode($item['unit_code'] ?? "PCE")
                ->setId($index + 1)
                ->setItem($productItem)
                ->setLineExtensionAmount($item['line_extension'] ?? $item['price'] ?? 0)
                ->setPrice($price)
                ->setTaxTotal($lineTaxTotal)
                ->setInvoicedQuantity($item['quantity'] ?? 1);

            $invoiceLines[] = $invoiceLine;
        }

        // === 11. Build Invoice ===
        $invoice = (new Invoice())
            ->setUBLExtensions($ublExtensions)
            ->setUUID($uuid)
            ->setId($invoiceData['invoice_id'] ?? 'INV001')
            ->setIssueDate($issueDate)
            ->setIssueTime($issueDate)
            ->setInvoiceType($invoiceType)
            ->setInvoiceCurrencyCode($invoiceData['currency'] ?? Config::get('zatca.currency', 'SAR'))
            ->setTaxCurrencyCode($invoiceData['currency'] ?? Config::get('zatca.currency', 'SAR'))
            ->setAdditionalDocumentReferences($additionalDocs)
            ->setAccountingSupplierParty($supplierCompany)
            ->setAccountingCustomerParty($supplierCustomer)
            ->setDelivery($delivery)
            ->setPaymentMeans($paymentMeans)
            ->setTaxTotal($taxTotal)
            ->setLegalMonetaryTotal($legalMonetaryTotal)
            ->setInvoiceLines($invoiceLines)
            ->setSignature($signature);

        // === 12. Generate XML ===
        return GeneratorInvoice::invoice($invoice, $invoiceData['currency'] ?? Config::get('zatca.currency', 'SAR'))->getXML();
    }
}

