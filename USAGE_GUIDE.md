# ZATCA Laravel Project - Usage Guide

## Complete Workflow

This guide shows you how to use the ZATCA Laravel project from start to finish.

## Step 1: Generate CSR and Certificate

### Generate CSR
```bash
php artisan zatca:generate-csr
```

This creates:
- `storage/app/zatca/output/certificate.csr`
- `storage/app/zatca/output/private.pem`

### Request Compliance Certificate
```bash
# In sandbox, you can use any OTP (e.g., 123123)
php artisan zatca:request-compliance-certificate 123123

# In production, use the OTP from ZATCA portal
php artisan zatca:request-compliance-certificate YOUR_OTP
```

This creates:
- `storage/app/zatca/output/ZATCA_certificate_data.json`

## Step 2: Generate Invoices from PHP Arrays

### Generate Invoice #1
```bash
php artisan zatca:generate-invoice-from-array 1
```

### Generate Invoice #2
```bash
php artisan zatca:generate-invoice-from-array 2
```

This creates unsigned invoice XML files in `storage/app/zatca/output/`

## Step 3: Sign Invoices

Sign the generated invoice:

```bash
php artisan zatca:sign-invoice Invoice_INV-001.xml
```

This will:
- Sign the invoice with your certificate
- Extract and save QR code to `storage/app/zatca/qr-codes/`
- Create signed invoice XML

## Step 4: Submit to ZATCA

Submit the signed invoice:

```bash
php artisan zatca:submit-invoice Invoice_INV-001_signed.xml
```

This will:
- Submit invoice to ZATCA API
- Save response to database (`zatca_submissions` table)
- Log success or failure

## Viewing Results

### Check Database Logs

```php
use App\Models\ZatcaSubmission;

// All submissions
$submissions = ZatcaSubmission::all();

// Successful submissions
$success = ZatcaSubmission::where('status', 'success')->get();

// Failed submissions
$failed = ZatcaSubmission::where('status', 'failed')->get();
```

### Check Files

- Unsigned invoices: `storage/app/zatca/output/Invoice_*.xml`
- Signed invoices: `storage/app/zatca/output/Invoice_*_signed.xml`
- QR codes: `storage/app/zatca/qr-codes/*.png`
- Certificates: `storage/app/zatca/output/`

## Custom Invoice Data

Edit `app/Data/SampleInvoices.php` to create your own invoice arrays, or use the `InvoiceService` directly:

```php
use App\Services\InvoiceService;

$invoiceData = [
    'invoice_id' => 'INV-003',
    'issue_date' => now()->toDateTimeString(),
    'invoice_type' => 'simplified',
    'currency' => 'SAR',
    'tax_percent' => 15,
    // ... add your invoice data
];

$service = new InvoiceService();
$xml = $service->generateFromArray($invoiceData);
```

## Available Commands

```bash
# Certificate Management
php artisan zatca:generate-csr
php artisan zatca:request-compliance-certificate [otp]

# Invoice Generation
php artisan zatca:generate-invoice                    # Original hardcoded invoice
php artisan zatca:generate-invoice-from-array {1|2}   # From PHP array

# Invoice Processing
php artisan zatca:sign-invoice [filename]
php artisan zatca:submit-invoice [filename]
```

## Project Structure

```
app/
├── Console/Commands/          # Artisan commands
├── Data/                      # Sample invoice data
├── Models/                    # Database models
└── Services/                  # Business logic services
    ├── InvoiceService.php    # Invoice generation
    └── QrCodeService.php     # QR code handling

storage/app/zatca/
├── output/                    # Generated files
│   ├── certificate.csr
│   ├── private.pem
│   ├── ZATCA_certificate_data.json
│   ├── Invoice_*.xml
│   └── Invoice_*_signed.xml
└── qr-codes/                  # QR code images
    └── *.png

database/
└── migrations/
    └── *_create_zatca_submissions_table.php
```

## Next Steps

1. Customize invoice data in `app/Data/SampleInvoices.php`
2. Integrate with your application logic
3. Set up production environment (see `PRODUCTION_SETUP.md`)
4. Monitor submissions via database logs

