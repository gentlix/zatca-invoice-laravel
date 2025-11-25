<?php

namespace App\Console\Commands;

use App\Models\ZatcaSubmission;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Saleh7\Zatca\Storage as ZatcaStorage;
use Sevaske\ZatcaApi\Api;
use Sevaske\ZatcaApi\Exceptions\ZatcaApiException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ZatcaSubmitInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zatca:submit-invoice {invoiceFile=Simplified_Invoice_signed.xml : The signed invoice XML file to submit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit signed invoice to ZATCA for compliance validation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $invoiceFileName = $this->argument('invoiceFile');
            $environment = Config::get('zatca.environment', 'sandbox');
            $disk = Storage::disk('local');
            $directory = 'zatca/output';
            $disk->makeDirectory($directory);

            $invoiceRelativePath = $directory.'/'.$invoiceFileName;
            if (! $disk->exists($invoiceRelativePath)) {
                $this->error("Signed invoice not found: {$invoiceFileName}");

                return SymfonyCommand::FAILURE;
            }

            $certificateJsonPath = $directory.'/ZATCA_certificate_data.json';
            if (! $disk->exists($certificateJsonPath)) {
                $this->error('Certificate data not found. Request a compliance certificate first.');

                return SymfonyCommand::FAILURE;
            }

            $this->info("Loading signed invoice: {$invoiceFileName}");
            $zatcaStorage = new ZatcaStorage();
            $signedInvoiceXml = $zatcaStorage->get($disk->path($invoiceRelativePath));

            $jsonCertificate = $zatcaStorage->get($disk->path($certificateJsonPath));
            $jsonData = json_decode($jsonCertificate, true, 512, JSON_THROW_ON_ERROR);

            $certificate = $jsonData['certificate'] ?? null;
            $secret = $jsonData['secret'] ?? null;

            if (! $certificate || ! $secret) {
                $this->error('Invalid certificate data file.');

                return SymfonyCommand::FAILURE;
            }

            $uuid = $this->extractUuidFromXml($signedInvoiceXml);
            $invoiceHash = $this->extractHashFromXml($signedInvoiceXml);
            $this->info("Invoice UUID: {$uuid}");
            $this->info("Invoice Hash: {$invoiceHash}");

            $zatcaClient = new Api($environment, new Client(), $certificate, $secret);

            $submissionType = 'compliance';
            $this->info("Submitting invoice to ZATCA ({$environment})...");
            $this->info("Submission type: {$submissionType}");

            // Extract invoice ID from filename or XML
            $invoiceId = $this->extractInvoiceIdFromXml($signedInvoiceXml) ?? pathinfo($invoiceFileName, PATHINFO_FILENAME);
            
            // Create submission record
            $submission = ZatcaSubmission::create([
                'invoice_id' => $invoiceId,
                'uuid' => $uuid,
                'invoice_hash' => $invoiceHash,
                'status' => 'pending',
                'submission_type' => $submissionType,
                'request_data' => [
                    'uuid' => $uuid,
                    'invoice_hash' => $invoiceHash,
                    'invoice_file' => $invoiceFileName,
                ],
                'invoice_file_path' => $disk->path($invoiceRelativePath),
                'signed_invoice_file_path' => $disk->path($invoiceRelativePath),
                'environment' => $environment,
            ]);

            try {
                $complianceResponse = $zatcaClient->compliance($signedInvoiceXml, $invoiceHash, $uuid);

                // Update submission with success
                $submission->update([
                    'status' => 'success',
                    'response_data' => is_array($complianceResponse) ? $complianceResponse : ['response' => $complianceResponse],
                    'zatca_request_id' => $complianceResponse['requestId'] ?? $complianceResponse['request_id'] ?? null,
                    'validation_status' => $complianceResponse['validationStatus'] ?? $complianceResponse['validation_status'] ?? null,
                    'validation_errors' => $complianceResponse['validationErrors'] ?? $complianceResponse['validation_errors'] ?? null,
                ]);

                $this->info('✓ Compliance submission successful!');
                $this->line('Response:');
                $this->line(print_r($complianceResponse, true));
                $this->info("Submission logged to database (ID: {$submission->id})");

                return SymfonyCommand::SUCCESS;
            } catch (ZatcaApiException $e) {
                // Update submission with error
                $submission->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'response_data' => method_exists($e, 'getContext') ? $e->getContext() : null,
                ]);

                throw $e;
            }
        } catch (ZatcaApiException $e) {
            $this->error('API Error: '.$e->getMessage());
            if (method_exists($e, 'getContext')) {
                $context = $e->getContext();
                if (! empty($context)) {
                    $this->line('Error details:');
                    $this->line(print_r($context, true));
                }
            }

            $this->line('Error Code: '.$e->getCode());
            $this->line('Location: '.$e->getFile().':'.$e->getLine());

            return SymfonyCommand::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());
            $this->line('Debug: '.$e->getFile().':'.$e->getLine());

            return SymfonyCommand::FAILURE;
        }
    }

    /**
     * Extract UUID from signed invoice XML.
     *
     * @throws \RuntimeException
     */
    private function extractUuidFromXml(string $xml): string
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        $uuidNodes = $xpath->query('//cbc:UUID');
        if ($uuidNodes->length > 0) {
            return $uuidNodes->item(0)->nodeValue;
        }

        throw new \RuntimeException('UUID not found in invoice XML');
    }

    /**
     * Extract invoice hash from signed invoice XML.
     *
     * @throws \RuntimeException
     */
    private function extractHashFromXml(string $xml): string
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $digestNodes = $xpath->query('//ds:Reference[@Id="invoiceSignedData"]/ds:DigestValue');
        if ($digestNodes->length > 0) {
            return $digestNodes->item(0)->nodeValue;
        }

        throw new \RuntimeException('Invoice hash not found in signed XML');
    }

    /**
     * Extract invoice ID from XML
     */
    private function extractInvoiceIdFromXml(string $xml): ?string
    {
        try {
            $doc = new DOMDocument();
            $doc->loadXML($xml);
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            
            $idNodes = $xpath->query('//cbc:ID');
            if ($idNodes->length > 0) {
                return $idNodes->item(0)->nodeValue;
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        return null;
    }
}

