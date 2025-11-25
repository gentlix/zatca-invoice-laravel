<?php

namespace App\Services;

use Saleh7\Zatca\{
    GeneratorInvoice, Invoice, InvoiceType, InvoiceLine, Item, Price, Party, LegalEntity,
    Address, PartyTaxScheme, TaxScheme, PaymentMeans, TaxTotal, TaxSubTotal, TaxCategory,
    LegalMonetaryTotal, ClassifiedTaxCategory, AllowanceCharge, Delivery, AdditionalDocumentReference,
    Attachment, UnitCode, Signature, SignatureInformation, UBLDocumentSignatures, ExtensionContent,
    UBLExtension, UBLExtensions
};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use DateTime;

class ZatcaInvoiceService
{
    protected string $invoicesPath;

    public function __construct()
    {
        $this->invoicesPath = config('zatca.paths.invoices');
    }

    public function generateInvoice(array $invoiceData, string $filename): array
    {
        try {
            $invoice = $this->buildInvoiceFromArray($invoiceData);

            $generator = GeneratorInvoice::invoice($invoice);
            $xml = $generator->getXML();

            $path = $this->invoicesPath . '/' . $filename;
            Storage::put($path, $xml);

            Log::info('Invoice generated', [
                'filename' => $filename,
                'path' => $path,
            ]);

            return [
                'path' => $path,
                'xml' => $xml,
                'uuid' => $invoiceData['uuid'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Invoice generation failed', [
                'error' => $e->getMessage(),
                'data' => $invoiceData,
            ]);
            throw $e;
        }
    }

    public function getSampleSimplifiedInvoice(): array
    {
        return [
            'uuid' => '3cf5ee18-ee25-44ea-a444-2c37ba7f28be',
            'id' => 'INV-' . date('YmdHis') . '-001',
            'issueDate' => now()->format('Y-m-d H:i:s'),
            'issueTime' => now()->format('Y-m-d H:i:s'),
            'currencyCode' => 'SAR',
            'taxCurrencyCode' => 'SAR',
            'note' => 'Sample Simplified Invoice',
            'languageID' => 'en',
            'invoiceType' => [
                'invoice' => 'simplified',
                'type' => 'invoice',
                'isThirdParty' => false,
                'isNominal' => false,
                'isExport' => false,
                'isSummary' => false,
                'isSelfBilled' => false,
            ],
            'additionalDocuments' => [
                [
                    'id' => 'ICV',
                    'uuid' => '1',
                ],
                [
                    'id' => 'PIH',
                    'attachment' => [
                        'content' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
                    ],
                ],
            ],
            'supplier' => [
                'registrationName' => config('zatca.organization.name'),
                'taxId' => config('zatca.organization.identifier'),
                'identificationId' => '1010010000',
                'identificationType' => 'CRN',
                'address' => [
                    'street' => 'Prince Sultan Street',
                    'buildingNumber' => '2322',
                    'subdivision' => 'Al-Murabba',
                    'city' => 'Riyadh',
                    'postalZone' => '12345',
                    'country' => 'SA',
                ],
            ],
            'customer' => [
                'registrationName' => 'Sample Customer',
                'taxId' => '399999999800003',
                'address' => [
                    'street' => 'Salah Al-Din Street',
                    'buildingNumber' => '1111',
                    'subdivision' => 'Al-Murooj',
                    'city' => 'Riyadh',
                    'postalZone' => '12222',
                    'country' => 'SA',
                ],
            ],
            'paymentMeans' => [
                'code' => '10',
            ],
            'allowanceCharges' => [
                [
                    'isCharge' => false,
                    'reason' => 'discount',
                    'amount' => 0.00,
                    'taxCategories' => [
                        [
                            'percent' => 15,
                            'taxScheme' => ['id' => 'VAT'],
                        ],
                    ],
                ],
            ],
            'taxTotal' => [
                'taxAmount' => 30.15,
                'subTotals' => [
                    [
                        'taxableAmount' => 201,
                        'taxAmount' => 30.15,
                        'taxCategory' => [
                            'percent' => 15,
                            'taxScheme' => ['id' => 'VAT'],
                        ],
                    ],
                ],
            ],
            'legalMonetaryTotal' => [
                'lineExtensionAmount' => 201,
                'taxExclusiveAmount' => 201,
                'taxInclusiveAmount' => 231.15,
                'prepaidAmount' => 0,
                'payableAmount' => 231.15,
                'allowanceTotalAmount' => 0,
            ],
            'invoiceLines' => [
                [
                    'id' => 1,
                    'unitCode' => 'PCE',
                    'quantity' => 33,
                    'lineExtensionAmount' => 99,
                    'item' => [
                        'name' => 'Product A',
                        'classifiedTaxCategory' => [
                            [
                                'percent' => 15,
                                'taxScheme' => ['id' => 'VAT'],
                            ],
                        ],
                    ],
                    'price' => [
                        'amount' => 3,
                        'unitCode' => 'UNIT',
                        'allowanceCharges' => [
                            [
                                'isCharge' => true,
                                'reason' => 'discount',
                                'amount' => 0.00,
                            ],
                        ],
                    ],
                    'taxTotal' => [
                        'taxAmount' => 14.85,
                        'roundingAmount' => 113.85,
                    ],
                ],
                [
                    'id' => 2,
                    'unitCode' => 'PCE',
                    'quantity' => 3,
                    'lineExtensionAmount' => 102,
                    'item' => [
                        'name' => 'Product B',
                        'classifiedTaxCategory' => [
                            [
                                'percent' => 15,
                                'taxScheme' => ['id' => 'VAT'],
                            ],
                        ],
                    ],
                    'price' => [
                        'amount' => 34,
                        'unitCode' => 'UNIT',
                        'allowanceCharges' => [
                            [
                                'isCharge' => true,
                                'reason' => 'discount',
                                'amount' => 0.00,
                            ],
                        ],
                    ],
                    'taxTotal' => [
                        'taxAmount' => 15.30,
                        'roundingAmount' => 117.30,
                    ],
                ],
            ],
        ];
    }

    public function getSampleStandardInvoice(): array
    {
        return [
            'uuid' => 'ec65d239-c793-452f-8e8c-509dbd54d2a9',
            'id' => 'INV-' . date('YmdHis') . '-002',
            'issueDate' => now()->format('Y-m-d H:i:s'),
            'issueTime' => now()->format('Y-m-d H:i:s'),
            'currencyCode' => 'SAR',
            'taxCurrencyCode' => 'SAR',
            'invoiceType' => [
                'invoice' => 'standard',
                'type' => 'invoice',
            ],
            'additionalDocuments' => [
                [
                    'id' => 'ICV',
                    'uuid' => '2',
                ],
                [
                    'id' => 'PIH',
                    'attachment' => [
                        'content' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
                    ],
                ],
            ],
            'supplier' => [
                'registrationName' => config('zatca.organization.name'),
                'taxId' => config('zatca.organization.identifier'),
                'identificationId' => '1010010000',
                'identificationType' => 'CRN',
                'address' => [
                    'street' => 'Prince Sultan Street',
                    'buildingNumber' => '2322',
                    'subdivision' => 'Al-Murabba',
                    'city' => 'Riyadh',
                    'postalZone' => '12345',
                    'country' => 'SA',
                ],
            ],
            'customer' => [
                'registrationName' => 'Sample Customer',
                'taxId' => '399999999800003',
                'address' => [
                    'street' => 'Salah Al-Din Street',
                    'buildingNumber' => '1111',
                    'subdivision' => 'Al-Murooj',
                    'city' => 'Riyadh',
                    'postalZone' => '12222',
                    'country' => 'SA',
                ],
            ],
            'paymentMeans' => [
                'code' => '10',
            ],
            'delivery' => [
                'actualDeliveryDate' => now()->format('Y-m-d'),
            ],
            'allowanceCharges' => [
                [
                    'isCharge' => false,
                    'reason' => 'discount',
                    'amount' => 0.00,
                    'taxCategories' => [
                        [
                            'percent' => 15,
                            'taxScheme' => ['id' => 'VAT'],
                        ],
                    ],
                ],
            ],
            'taxTotal' => [
                'taxAmount' => 0.6,
                'subTotals' => [
                    [
                        'taxableAmount' => 4,
                        'taxAmount' => 0.6,
                        'taxCategory' => [
                            'percent' => 15,
                            'taxScheme' => ['id' => 'VAT'],
                        ],
                    ],
                ],
            ],
            'legalMonetaryTotal' => [
                'lineExtensionAmount' => 4,
                'taxExclusiveAmount' => 4,
                'taxInclusiveAmount' => 4.60,
                'prepaidAmount' => 0,
                'payableAmount' => 4.60,
                'allowanceTotalAmount' => 0,
            ],
            'invoiceLines' => [
                [
                    'id' => 1,
                    'unitCode' => 'PCE',
                    'quantity' => 2,
                    'lineExtensionAmount' => 4,
                    'item' => [
                        'name' => 'Product',
                        'classifiedTaxCategory' => [
                            [
                                'percent' => 15,
                                'taxScheme' => ['id' => 'VAT'],
                            ],
                        ],
                    ],
                    'price' => [
                        'amount' => 2,
                        'unitCode' => 'UNIT',
                        'allowanceCharges' => [
                            [
                                'isCharge' => true,
                                'reason' => 'discount',
                                'amount' => 0.00,
                            ],
                        ],
                    ],
                    'taxTotal' => [
                        'taxAmount' => 0.60,
                        'roundingAmount' => 4.60,
                    ],
                ],
            ],
        ];
    }

    protected function buildInvoiceFromArray(array $data): Invoice
    {
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

        $signature = (new Signature)
            ->setId("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setSignatureMethod("urn:oasis:names:specification:ubl:dsig:enveloped:xades");

        $invoiceTypeData = $data['invoiceType'] ?? [];
        $invoiceType = (new InvoiceType())
            ->setInvoice($invoiceTypeData['invoice'] ?? 'standard')
            ->setInvoiceType($invoiceTypeData['type'] ?? 'invoice')
            ->setIsThirdParty($invoiceTypeData['isThirdParty'] ?? false)
            ->setIsNominal($invoiceTypeData['isNominal'] ?? false)
            ->setIsExportInvoice($invoiceTypeData['isExport'] ?? false)
            ->setIsSummary($invoiceTypeData['isSummary'] ?? false)
            ->setIsSelfBilled($invoiceTypeData['isSelfBilled'] ?? false);

        $additionalDocs = [];
        if (isset($data['additionalDocuments'])) {
            foreach ($data['additionalDocuments'] as $doc) {
                $additionalDoc = (new AdditionalDocumentReference())->setId($doc['id']);
                
                if (isset($doc['uuid'])) {
                    $additionalDoc->setUUID($doc['uuid']);
                }
                
                if (isset($doc['attachment']['content'])) {
                    $attachment = (new Attachment())
                        ->setBase64Content($doc['attachment']['content'], 'base64', 'text/plain');
                    $additionalDoc->setAttachment($attachment);
                }
                
                $additionalDocs[] = $additionalDoc;
            }
        }

        $taxScheme = (new TaxScheme())->setId("VAT");

        $supplierData = $data['supplier'] ?? [];
        $supplierAddress = $this->buildAddress($supplierData['address'] ?? []);
        $supplierLegalEntity = (new LegalEntity())
            ->setRegistrationName($supplierData['registrationName'] ?? '');
        
        $supplierPartyTaxScheme = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId($supplierData['taxId'] ?? '');

        $supplierParty = (new Party())
            ->setPartyIdentification($supplierData['taxId'] ?? '')
            ->setPartyIdentificationId($supplierData['identificationType'] ?? 'CRN')
            ->setLegalEntity($supplierLegalEntity)
            ->setPartyTaxScheme($supplierPartyTaxScheme)
            ->setPostalAddress($supplierAddress);

        $customerData = $data['customer'] ?? [];
        $customerAddress = $this->buildAddress($customerData['address'] ?? []);
        $customerLegalEntity = (new LegalEntity())
            ->setRegistrationName($customerData['registrationName'] ?? '');
        
        $customerPartyTaxScheme = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme);

        $customerParty = (new Party())
            ->setPartyIdentification($customerData['taxId'] ?? '')
            ->setPartyIdentificationId($customerData['identificationType'] ?? 'NAT')
            ->setLegalEntity($customerLegalEntity)
            ->setPartyTaxScheme($customerPartyTaxScheme)
            ->setPostalAddress($customerAddress);

        $paymentMeans = null;
        if (isset($data['paymentMeans'])) {
            $paymentMeans = (new PaymentMeans())
                ->setPaymentMeansCode($data['paymentMeans']['code'] ?? '10');
        }

        $delivery = null;
        if (isset($data['delivery'])) {
            $delivery = (new Delivery())
                ->setActualDeliveryDate($data['delivery']['actualDeliveryDate'] ?? date('Y-m-d'));
        }

        $allowanceCharges = [];
        if (isset($data['allowanceCharges'])) {
            foreach ($data['allowanceCharges'] as $acData) {
                $taxCategory = (new TaxCategory())
                    ->setPercent($acData['taxCategories'][0]['percent'] ?? 15)
                    ->setTaxScheme($taxScheme);
                
                $allowanceCharge = (new AllowanceCharge())
                    ->setChargeIndicator($acData['isCharge'] ?? false)
                    ->setAllowanceChargeReason($acData['reason'] ?? '')
                    ->setAmount($acData['amount'] ?? 0.00)
                    ->setTaxCategory($taxCategory);
                
                $allowanceCharges[] = $allowanceCharge;
            }
        }

        $taxTotal = null;
        if (isset($data['taxTotal'])) {
            $taxSubTotals = [];
            foreach ($data['taxTotal']['subTotals'] ?? [] as $subTotalData) {
                $taxCategory = (new TaxCategory())
                    ->setPercent($subTotalData['taxCategory']['percent'] ?? 15)
                    ->setTaxScheme($taxScheme);
                
                $taxSubTotal = (new TaxSubTotal())
                    ->setTaxableAmount($subTotalData['taxableAmount'] ?? 0)
                    ->setTaxAmount($subTotalData['taxAmount'] ?? 0)
                    ->setTaxCategory($taxCategory);
                
                $taxSubTotals[] = $taxSubTotal;
            }
            
            $taxTotal = (new TaxTotal())
                ->setTaxAmount($data['taxTotal']['taxAmount'] ?? 0);
            
            foreach ($taxSubTotals as $subTotal) {
                $taxTotal->addTaxSubTotal($subTotal);
            }
        }

        $legalMonetaryTotal = null;
        if (isset($data['legalMonetaryTotal'])) {
            $legalMonetaryTotal = (new LegalMonetaryTotal())
                ->setLineExtensionAmount($data['legalMonetaryTotal']['lineExtensionAmount'] ?? 0)
                ->setTaxExclusiveAmount($data['legalMonetaryTotal']['taxExclusiveAmount'] ?? 0)
                ->setTaxInclusiveAmount($data['legalMonetaryTotal']['taxInclusiveAmount'] ?? 0)
                ->setPrepaidAmount($data['legalMonetaryTotal']['prepaidAmount'] ?? 0)
                ->setPayableAmount($data['legalMonetaryTotal']['payableAmount'] ?? 0)
                ->setAllowanceTotalAmount($data['legalMonetaryTotal']['allowanceTotalAmount'] ?? 0);
        }

        $invoiceLines = [];
        if (isset($data['invoiceLines'])) {
            foreach ($data['invoiceLines'] as $lineData) {
                $classifiedTax = (new ClassifiedTaxCategory())
                    ->setPercent($lineData['item']['classifiedTaxCategory'][0]['percent'] ?? 15)
                    ->setTaxScheme($taxScheme);
                
                $item = (new Item())
                    ->setName($lineData['item']['name'] ?? '')
                    ->setClassifiedTaxCategory($classifiedTax);
                
                $price = (new Price())
                    ->setUnitCode(UnitCode::UNIT)
                    ->setPriceAmount($lineData['price']['amount'] ?? 0);
                
                $lineTaxTotal = (new TaxTotal())
                    ->setTaxAmount($lineData['taxTotal']['taxAmount'] ?? 0)
                    ->setRoundingAmount($lineData['taxTotal']['roundingAmount'] ?? 0);
                
                $invoiceLine = (new InvoiceLine())
                    ->setUnitCode($lineData['unitCode'] ?? 'PCE')
                    ->setId($lineData['id'] ?? 1)
                    ->setItem($item)
                    ->setLineExtensionAmount($lineData['lineExtensionAmount'] ?? 0)
                    ->setPrice($price)
                    ->setTaxTotal($lineTaxTotal)
                    ->setInvoicedQuantity($lineData['quantity'] ?? 1);
                
                $invoiceLines[] = $invoiceLine;
            }
        }

        $invoice = (new Invoice())
            ->setUBLExtensions($ublExtensions)
            ->setUUID($data['uuid'] ?? '')
            ->setId($data['id'] ?? '')
            ->setIssueDate(new DateTime($data['issueDate'] ?? 'now'))
            ->setIssueTime(new DateTime($data['issueTime'] ?? 'now'))
            ->setInvoiceType($invoiceType)
            ->setlanguageID($data['languageID'] ?? 'en')
            ->setInvoiceCurrencyCode($data['currencyCode'] ?? 'SAR')
            ->setTaxCurrencyCode($data['taxCurrencyCode'] ?? 'SAR')
            ->setAdditionalDocumentReferences($additionalDocs)
            ->setAccountingSupplierParty($supplierParty)
            ->setAccountingCustomerParty($customerParty)
            ->setPaymentMeans($paymentMeans)
            ->setAllowanceCharges($allowanceCharges)
            ->setTaxTotal($taxTotal)
            ->setLegalMonetaryTotal($legalMonetaryTotal)
            ->setInvoiceLines($invoiceLines)
            ->setSignature($signature);

        if ($delivery) {
            $invoice->setDelivery($delivery);
        }

        if (isset($data['note'])) {
            $invoice->setNote($data['note']);
        }

        return $invoice;
    }

    protected function buildAddress(array $addressData): Address
    {
        return (new Address())
            ->setStreetName($addressData['street'] ?? '')
            ->setBuildingNumber($addressData['buildingNumber'] ?? '')
            ->setCitySubdivisionName($addressData['subdivision'] ?? '')
            ->setCityName($addressData['city'] ?? '')
            ->setPostalZone($addressData['postalZone'] ?? '')
            ->setCountry($addressData['country'] ?? 'SA');
    }
}

