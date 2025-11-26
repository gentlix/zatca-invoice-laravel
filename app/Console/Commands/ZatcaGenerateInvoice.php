<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
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

class ZatcaGenerateInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zatca:generate-invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate both Standard and Simplified ZATCA-compliant invoices (XML)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating both Standard and Simplified invoices...');
        $this->newLine();

        $storage = Storage::disk('local');
        $directory = 'zatca/output';
        $storage->makeDirectory($directory);
        $currency = Config::get('zatca.currency', 'SAR');
        $issueDate = now()->utc();

        $successCount = 0;
        $failCount = 0;

        // Generate Standard Invoice
        try {
            $this->info('📄 Generating STANDARD Invoice...');
            $standardInvoice = $this->buildInvoice('standard', 'STD001', $issueDate, $currency);
            $standardPath = $directory.'/Standard_Invoice.xml';
            $standardAbsolutePath = $storage->path($standardPath);
            GeneratorInvoice::invoice($standardInvoice)->saveXMLFile($standardAbsolutePath, null);
            
            $this->info("  ✓ STANDARD Invoice generated successfully!");
            $this->info("    Invoice ID: STD001");
            $this->info("    File saved to: {$standardAbsolutePath}");
            $successCount++;
            $this->newLine();
        } catch (\Throwable $e) {
            $this->error("  ✗ Error generating STANDARD invoice: ".$e->getMessage());
            $this->line("    File: {$e->getFile()}:{$e->getLine()}");
            $failCount++;
            $this->newLine();
        }

        // Generate Simplified Invoice
        try {
            $this->info('📄 Generating SIMPLIFIED Invoice...');
            $simplifiedInvoice = $this->buildInvoice('simplified', 'SME00099', $issueDate, $currency);
            $simplifiedPath = $directory.'/Simplified_Invoice.xml';
            $simplifiedAbsolutePath = $storage->path($simplifiedPath);
            GeneratorInvoice::invoice($simplifiedInvoice)->saveXMLFile($simplifiedAbsolutePath, null);
            
            $this->info("  ✓ SIMPLIFIED Invoice generated successfully!");
            $this->info("    Invoice ID: SME00099");
            $this->info("    File saved to: {$simplifiedAbsolutePath}");
            $successCount++;
            $this->newLine();
        } catch (\Throwable $e) {
            $this->error("  ✗ Error generating SIMPLIFIED invoice: ".$e->getMessage());
            $this->line("    File: {$e->getFile()}:{$e->getLine()}");
            $failCount++;
            $this->newLine();
        }

        // Summary
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        if ($successCount === 2) {
            $this->info("✓ Successfully generated both invoices!");
            $this->info("  • Standard Invoice: Standard_Invoice.xml");
            $this->info("  • Simplified Invoice: Simplified_Invoice.xml");
            return Command::SUCCESS;
        } else {
            $this->warn("⚠ Generated {$successCount} invoice(s), {$failCount} failed");
            return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
        }
    }

    /**
     * Build invoice object
     */
    private function buildInvoice(string $invoiceType, string $invoiceId, $issueDate, string $currency): Invoice
    {
        $uuid = Str::uuid()->toString();

        // 1. UBL Signature Extensions
        $signatureInfo = (new SignatureInformation())
            ->setReferencedSignatureID('urn:oasis:names:specification:ubl:signature:Invoice')
            ->setID('urn:oasis:names:specification:ubl:signature:1');

        $ublDocSignatures = (new UBLDocumentSignatures())
            ->setSignatureInformation($signatureInfo);

        $extensionContent = (new ExtensionContent())
            ->setUBLDocumentSignatures($ublDocSignatures);

        $ublExtension = (new UBLExtension())
            ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
            ->setExtensionContent($extensionContent);

        $ublExtensions = (new UBLExtensions())
            ->setUBLExtensions([$ublExtension]);

        // 2. Signature Section
        $signature = (new Signature())
            ->setId('urn:oasis:names:specification:ubl:signature:Invoice')
            ->setSignatureMethod('urn:oasis:names:specification:ubl:dsig:enveloped:xades');

        // 3. Invoice Type
        $invoiceTypeObj = (new InvoiceType())
            ->setInvoice($invoiceType)
            ->setInvoiceType('invoice')
            ->setIsThirdParty(false);

        // 4. Attachments
        $attachment = (new Attachment())
            ->setBase64Content(
                'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
                'base64',
                'text/plain'
            );

        // 5. Additional Document References
        $additionalDocs = [
            (new AdditionalDocumentReference())->setId('ICV')->setUUID('10'),
            (new AdditionalDocumentReference())->setId('PIH')->setAttachment($attachment),
            (new AdditionalDocumentReference())->setId('QR'),
        ];

        // 6. Supplier / Customer Info
        $taxScheme = (new TaxScheme())->setId('VAT');

        $partyTaxSchemeSupplier = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId(Config::get('zatca.organization.identifier', '300000000000003'));

        $partyTaxSchemeCustomer = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme);

        $addressSupplier = (new Address())
            ->setStreetName(Config::get('zatca.organization.address', 'Riyadh King Fahd Road'))
            ->setBuildingNumber(Config::get('zatca.organization.building_number', '8008'))
            ->setCitySubdivisionName(Config::get('zatca.organization.city_subdivision_name', 'Al Olaya'))
            ->setCityName(Config::get('zatca.organization.city', 'Riyadh'))
            ->setPostalZone(Config::get('zatca.organization.postal_code', '12345'))
            ->setCountry(Config::get('zatca.organization.country_code', 'SA'));

        $addressCustomer = (new Address())
            ->setStreetName('Salah Al-Din')
            ->setBuildingNumber('1111')
            ->setCitySubdivisionName('Al-Murooj')
            ->setCityName('Riyadh')
            ->setPostalZone('12222')
            ->setCountry('SA');

        $legalEntitySupplier = (new LegalEntity())
            ->setRegistrationName(Config::get('zatca.organization.name', 'Name Company'));

        $legalEntityCustomer = (new LegalEntity())
            ->setRegistrationName('Fatoora Samples');

        $supplierCompany = (new Party())
            ->setPartyIdentification(Config::get('zatca.organization.registration_number', '1000000000'))
            ->setPartyIdentificationId('CRN')
            ->setLegalEntity($legalEntitySupplier)
            ->setPartyTaxScheme($partyTaxSchemeSupplier)
            ->setPostalAddress($addressSupplier);

        $supplierCustomer = (new Party())
            ->setPartyIdentification('1000000000')
            ->setPartyIdentificationId('NAT')
            ->setLegalEntity($legalEntityCustomer)
            ->setPartyTaxScheme($partyTaxSchemeCustomer)
            ->setPostalAddress($addressCustomer);

        // 7. Delivery / Payment
        $delivery = (new Delivery())->setActualDeliveryDate($issueDate->format('Y-m-d'));
        $paymentMeans = (new PaymentMeans())->setPaymentMeansCode('10');

        // 8. Tax Structure
        $taxCategory = (new TaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        $taxSubTotal = (new TaxSubTotal())
            ->setTaxableAmount(4.00)
            ->setTaxAmount(0.60)
            ->setTaxCategory($taxCategory);

        $taxTotal = (new TaxTotal())
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount(0.60);

        // 9. Totals
        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setLineExtensionAmount(4.00)
            ->setTaxExclusiveAmount(4.00)
            ->setTaxInclusiveAmount(4.60)
            ->setAllowanceTotalAmount(0)
            ->setPrepaidAmount(0)
            ->setPayableAmount(4.60);

        // 10. Line Items
        $classifiedTax = (new ClassifiedTaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        $productItem = (new Item())
            ->setName('Product')
            ->setClassifiedTaxCategory($classifiedTax);

        $price = (new Price())
            ->setUnitCode(UnitCode::UNIT)
            ->setPriceAmount(2.00);

        $lineTaxTotal = (new TaxTotal())
            ->setTaxAmount(0.60)
            ->setRoundingAmount(4.60);

        $invoiceLine = (new InvoiceLine())
            ->setUnitCode('PCE')
            ->setId(1)
            ->setItem($productItem)
            ->setLineExtensionAmount(4.00)
            ->setPrice($price)
            ->setTaxTotal($lineTaxTotal)
            ->setInvoicedQuantity(2);

        // 11. Build Invoice
        return (new Invoice())
            ->setUBLExtensions($ublExtensions)
            ->setUUID($uuid)
            ->setId($invoiceId)
            ->setIssueDate($issueDate)
            ->setIssueTime($issueDate)
            ->setInvoiceType($invoiceTypeObj)
            ->setInvoiceCurrencyCode($currency)
            ->setTaxCurrencyCode($currency)
            ->setAdditionalDocumentReferences($additionalDocs)
            ->setAccountingSupplierParty($supplierCompany)
            ->setAccountingCustomerParty($supplierCustomer)
            ->setDelivery($delivery)
            ->setPaymentMeans($paymentMeans)
            ->setTaxTotal($taxTotal)
            ->setLegalMonetaryTotal($legalMonetaryTotal)
            ->setInvoiceLines([$invoiceLine])
            ->setSignature($signature);
    }
}
