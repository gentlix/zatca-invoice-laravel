# ZATCA Laravel Application

A complete Laravel 11 application with full ZATCA (Saudi Arabia Tax Authority) e-invoicing integration. This project provides a complete workflow for generating, signing, and submitting ZATCA-compliant invoices.

## 📋 Table of Contents

- [Requirements](#-requirements)
- [Quick Start](#-quick-start)
- [Installation & Setup](#-installation--setup)
- [Available Commands](#-available-commands)
- [Complete Workflow](#-complete-workflow)
- [Project Structure](#-project-structure)
- [Configuration](#-configuration)
- [QR Code Guide](#-qr-code-guide)
- [Production Setup](#-production-setup)
- [Database Schema](#-database-schema)
- [Troubleshooting](#-troubleshooting)
- [Resources](#-resources)

## 📋 Requirements

- **PHP >= 8.2** (with OpenSSL extension)
- **Composer**
- **MySQL/MariaDB** (for database logging)
- **Node.js & NPM** (optional, for frontend assets)

## 🚀 Quick Start

### 1. Install Dependencies

```bash
composer install
```

### 2. Create Environment File

**Windows PowerShell:**
```powershell
if (!(Test-Path .env)) { 
    @"
APP_NAME=ZATCA
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

ZATCA_ENVIRONMENT=sandbox
ZATCA_ORGANIZATION_IDENTIFIER=300000000000003
ZATCA_ORGANIZATION_NAME=Your Company Name
ZATCA_REGISTRATION_NUMBER=1234567890
ZATCA_DEVICE_SERIAL_NUMBER=DEVICE001
ZATCA_INVOICE_TYPE=1100
"@ | Out-File -FilePath .env -Encoding utf8
}
```

**Linux/Mac:**
```bash
cp .env.example .env
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Verify Installation

```bash
php artisan list zatca
```

You should see 6 commands listed.

## 📦 Installation & Setup

### Step 1: Install PHP Dependencies

```bash
composer install
```

This installs:
- Laravel 11 framework
- `sevaske/php-zatca-xml` (v3.4+) - Invoice generation and signing
- `sevaske/zatca-api` (v1.0+) - ZATCA API integration
- `simplesoftwareio/simple-qrcode` - QR code generation
- All other required dependencies

### Step 2: Configure Environment

Edit `.env` and update ZATCA settings:

```env
# ZATCA Configuration
ZATCA_ENVIRONMENT=sandbox                    # or 'production'
ZATCA_ORGANIZATION_IDENTIFIER=300000000000003  # 15 digits, start/end with 3
ZATCA_ORGANIZATION_NAME=Your Company Name
ZATCA_REGISTRATION_NUMBER=1234567890          # 10 digits
ZATCA_DEVICE_SERIAL_NUMBER=DEVICE001
ZATCA_INVOICE_TYPE=1100                       # Invoice type code
ZATCA_CURRENCY=SAR
ZATCA_ADDRESS=Your Street Address
ZATCA_BUILDING_NUMBER=1234
ZATCA_CITY_SUBDIVISION_NAME=District Name
ZATCA_CITY=Riyadh
ZATCA_POSTAL_CODE=12345
ZATCA_COUNTRY_CODE=SA
```

### Step 3: Run Database Migrations

```bash
php artisan migrate
```

This creates the `zatca_submissions` table for logging API responses.

## 🎯 Available Commands

This project includes 7 Artisan commands for the complete ZATCA workflow:

### Certificate Management

#### 1. Generate CSR and Private Key
```bash
php artisan zatca:generate-csr
```
**Output:**
- `storage/app/zatca/output/certificate.csr`
- `storage/app/zatca/output/private.pem`

#### 2. Request Compliance Certificate
```bash
php artisan zatca:request-compliance-certificate [otp]
```
**Note:** In sandbox, you can use any OTP (e.g., `123123`). In production, use the OTP from ZATCA portal.

**Output:**
- `storage/app/zatca/output/ZATCA_certificate_data.json`

### Invoice Generation

#### 3. Generate Invoice
```bash
php artisan zatca:generate-invoice
```
Generates **both** Standard and Simplified ZATCA-compliant invoices in one command.

**Output:**
- `storage/app/zatca/output/Standard_Invoice.xml` (Standard Invoice)
- `storage/app/zatca/output/Simplified_Invoice.xml` (Simplified Invoice)

#### 4. Generate Invoices from PHP Array (Optional)
```bash
php artisan zatca:generate-invoice-from-array
```
Alternative command that generates **both** Standard and Simplified invoices from PHP array data:
- **Standard Invoice**: `Invoice_INV-001.xml` (`invoice_type: 'standard'`)
- **Simplified Invoice**: `Invoice_INV-002.xml` (`invoice_type: 'simplified'`)

**Output:**
- `storage/app/zatca/output/Invoice_INV-001.xml` (Standard Invoice)
- `storage/app/zatca/output/Invoice_INV-002.xml` (Simplified Invoice)

**Note:** This command uses structured PHP arrays from `SampleInvoices` class, while `zatca:generate-invoice` uses hardcoded data. Both generate the same invoice types.

### Invoice Processing

#### 5. Sign Invoices
```bash
php artisan zatca:sign-invoice
```
Signs **both** Standard and Simplified invoices with the compliance certificate and extracts QR codes automatically.

**Output:**
- `storage/app/zatca/output/Standard_Invoice_signed.xml`
- `storage/app/zatca/output/Simplified_Invoice_signed.xml`
- `storage/app/zatca/qr-codes/Standard_Invoice.svg` (or .png)
- `storage/app/zatca/qr-codes/Simplified_Invoice.svg` (or .png)

**Note:** Also signs array-generated invoices (`Invoice_INV-001.xml` and `Invoice_INV-002.xml`) if they exist.

#### 6. Submit Invoice to ZATCA
```bash
php artisan zatca:submit-invoice [invoiceFile]
```
Submits the signed invoice to ZATCA for compliance validation and logs the response to database.

#### 7. Clean History
```bash
php artisan zatca:clean-history [options]
```
Cleans up old ZATCA files and database logs.

**Options:**
- `--invoices` - Clean unsigned and signed invoice XML files
- `--qr-codes` - Clean QR code images
- `--database` - Clean database submission logs
- `--all` - Clean all history (invoices, QR codes, and database logs)
- `--days=30` - Number of days to keep (default: 30)
- `--force` - Force clean all files regardless of age (use with caution!)

**Examples:**
```bash
# Clean all history older than 30 days (default)
php artisan zatca:clean-history --all

# Force clean ALL files regardless of age (use with caution!)
php artisan zatca:clean-history --all --force

# Clean only invoices older than 7 days
php artisan zatca:clean-history --invoices --days=7

# Clean database logs older than 90 days
php artisan zatca:clean-history --database --days=90

# Clean QR codes older than 14 days
php artisan zatca:clean-history --qr-codes --days=14
```

**Important Notes:**
- By default, only files older than the specified days are deleted
- Use `--force` to delete **ALL producible files** including:
  - Invoice XML files (unsigned and signed)
  - Certificate files (CSR, PEM, JSON certificate data)
  - QR code images
  - Database submission logs
- **Warning:** Using `--force` will delete certificates. You'll need to regenerate them using `zatca:generate-csr` and `zatca:request-compliance-certificate`

## 🔄 Complete Workflow

### Full Invoice Generation Flow

```bash
# Step 1: Generate CSR and private key
php artisan zatca:generate-csr

# Step 2: Request compliance certificate (sandbox: use any OTP)
php artisan zatca:request-compliance-certificate 123123

# Step 3: Generate both invoices (Standard + Simplified)
# Option 1: Using main command (hardcoded data)
php artisan zatca:generate-invoice

# Option 2: Using array command (from PHP arrays)
php artisan zatca:generate-invoice-from-array

# Step 4: Sign both invoices (extracts QR codes automatically)
php artisan zatca:sign-invoice

# Step 5: Submit to ZATCA (logs to database)
php artisan zatca:submit-invoice Standard_Invoice_signed.xml      # Submit Standard
php artisan zatca:submit-invoice Simplified_Invoice_signed.xml    # Submit Simplified

# Step 6: Clean history (optional - remove old files)
php artisan zatca:clean-history --all --days=30
```

### Viewing Results

**Check Database Logs:**
```php
use App\Models\ZatcaSubmission;

// All submissions
$submissions = ZatcaSubmission::all();

// Successful submissions
$success = ZatcaSubmission::where('status', 'success')->get();

// Failed submissions
$failed = ZatcaSubmission::where('status', 'failed')->get();
```

**Check Files:**
- Unsigned invoices: `storage/app/zatca/output/Invoice_*.xml`
- Signed invoices: `storage/app/zatca/output/Invoice_*_signed.xml`
- QR codes: `storage/app/zatca/qr-codes/*.svg`
- Certificates: `storage/app/zatca/output/`

## 📁 Project Structure

```
zatca-laravel/
├── app/
│   ├── Console/Commands/
│   │   ├── ZatcaCleanHistory.php
│   │   ├── ZatcaGenerateCsr.php
│   │   ├── ZatcaGenerateInvoice.php
│   │   ├── ZatcaGenerateInvoiceFromArray.php
│   │   ├── ZatcaRequestComplianceCertificate.php
│   │   ├── ZatcaSignInvoice.php
│   │   └── ZatcaSubmitInvoice.php
│   ├── Data/
│   │   └── SampleInvoices.php          # Sample invoice data arrays
│   ├── Models/
│   │   └── ZatcaSubmission.php         # Database model for logs
│   └── Services/
│       └── InvoiceService.php          # Invoice generation service
├── config/
│   └── zatca.php                       # ZATCA configuration
├── database/migrations/
│   └── *_create_zatca_submissions_table.php
├── storage/app/zatca/
│   ├── output/                          # Certificates, invoices
│   └── qr-codes/                       # QR code images
└── .env                                # Environment configuration
```

## ⚙️ Configuration

### Environment Variables

All ZATCA settings are configured in `.env`:

```env
# Environment
ZATCA_ENVIRONMENT=sandbox              # or 'production'

# Organization Details
ZATCA_ORGANIZATION_IDENTIFIER=300000000000003  # 15 digits, start/end with 3
ZATCA_ORGANIZATION_NAME=Your Company Name
ZATCA_REGISTRATION_NUMBER=1234567890         # 10 digits
ZATCA_UNIT=Auction System
ZATCA_COMMON_NAME=Auction System
ZATCA_BUSINESS_CATEGORY=Your Business Category

# Address
ZATCA_ADDRESS=Your Street Address
ZATCA_BUILDING_NUMBER=1234
ZATCA_CITY_SUBDIVISION_NAME=District Name
ZATCA_CITY=Riyadh
ZATCA_POSTAL_CODE=12345
ZATCA_COUNTRY_CODE=SA

# Device/Solution
ZATCA_SOLUTION_NAME=Your Solution Name
ZATCA_SOLUTION_MODEL=v1.0
ZATCA_DEVICE_SERIAL_NUMBER=DEVICE001

# Invoice
ZATCA_INVOICE_TYPE=1100                # Invoice type code
ZATCA_CURRENCY=SAR
```

### Configuration File

See `config/zatca.php` for all available configuration options and defaults.

## 📱 QR Code Guide

### QR Code Format

QR codes are generated and saved as:

- **SVG Format (Default)**: `storage/app/zatca/qr-codes/{invoice_id}.svg`
  - Opens in web browsers, image viewers
  - Vector format, scalable
  - Works without imagick extension

- **PNG Format (If imagick installed)**: `storage/app/zatca/qr-codes/{invoice_id}.png`
  - Raster format, widely compatible

### Opening QR Code Files

**SVG Files:**
1. **Double-click** the `.svg` file - opens in default browser
2. **Right-click** → Open with → Choose application
3. **Drag and drop** into browser window

**Converting SVG to PNG:**
- Install imagick extension (see below)
- Use online converter: https://convertio.co/svg-png/
- Use ImageMagick: `magick convert input.svg output.png`

### Installing imagick for PNG Support

**Windows (XAMPP):**
1. Download imagick DLL from: https://pecl.php.net/package/imagick
2. Copy `php_imagick.dll` to `php/ext/` folder
3. Edit `php.ini` and add: `extension=imagick`
4. Restart Apache/PHP

**Linux:**
```bash
sudo apt-get install php-imagick
sudo systemctl restart php-fpm
```

## 🚀 Production Setup

### Prerequisites

Before connecting to production:
1. ✅ Registered organization with ZATCA
2. ✅ Obtained production credentials from ZATCA portal
3. ✅ Completed ZATCA onboarding
4. ✅ Received production OTP from ZATCA

### Step 1: Update Environment

Edit `.env` and change:

```env
ZATCA_ENVIRONMENT=production
ZATCA_ORGANIZATION_IDENTIFIER=your_15_digit_identifier
ZATCA_ORGANIZATION_NAME=Your Actual Company Name
ZATCA_REGISTRATION_NUMBER=your_10_digit_registration
ZATCA_DEVICE_SERIAL_NUMBER=your_unique_device_serial
```

### Step 2: Generate Production CSR

```bash
php artisan zatca:generate-csr
```

**Important:** Keep your private key secure! Never share it or commit to version control.

### Step 3: Request Production Certificate

1. Log in to ZATCA portal: https://zatca.gov.sa/
2. Upload your `certificate.csr` file
3. Receive OTP via email/SMS
4. Request certificate:

```bash
php artisan zatca:request-compliance-certificate YOUR_PRODUCTION_OTP
```

**Note:** In production, you must use the actual OTP from ZATCA. Unlike sandbox, random OTPs won't work.

### Step 4: Verify Production Connection

```bash
# Generate test invoice
php artisan zatca:generate-invoice-from-array 1

# Sign it
php artisan zatca:sign-invoice Invoice_INV-001.xml

# Submit to production
php artisan zatca:submit-invoice Invoice_INV-001_signed.xml
```

### Production Checklist

Before going live:
- [ ] Environment set to `production` in `.env`
- [ ] All organization details are correct
- [ ] Production CSR generated
- [ ] Production compliance certificate obtained
- [ ] Database configured and migrations run
- [ ] Test invoice submitted successfully
- [ ] QR codes generating correctly
- [ ] Submission logs saving
- [ ] SSL/TLS configured
- [ ] Backups in place

### Security Considerations

1. **Secure Storage**
   - Store private keys and certificates securely
   - Use environment variables for sensitive data
   - Never commit `.env` to version control
   - Use secure file permissions (600) for certificate files

2. **SSL/TLS**
   - Ensure application uses HTTPS in production
   - ZATCA API requires secure connections

3. **Backup**
   - Regularly backup certificates and private keys
   - Store backups in secure, encrypted locations

### API Endpoints

- **Sandbox**: `https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal/`
- **Production**: `https://gw-fatoora.zatca.gov.sa/e-invoicing/core/`

The application automatically uses the correct endpoint based on `ZATCA_ENVIRONMENT`.

## 💾 Database Schema

### zatca_submissions Table

Logs all ZATCA API submission responses:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `invoice_id` | varchar(255) | Invoice identifier |
| `uuid` | varchar(255) | Invoice UUID |
| `invoice_hash` | varchar(255) | Invoice hash |
| `status` | varchar(255) | pending/success/failed |
| `submission_type` | varchar(255) | compliance/reporting |
| `request_data` | text | JSON request data |
| `response_data` | text | JSON response data |
| `error_message` | text | Error details |
| `zatca_request_id` | varchar(255) | ZATCA request ID |
| `validation_status` | varchar(255) | VALID/INVALID |
| `validation_errors` | text | JSON validation errors |
| `invoice_file_path` | varchar(255) | Path to unsigned invoice |
| `signed_invoice_file_path` | varchar(255) | Path to signed invoice |
| `qr_code_path` | varchar(255) | Path to QR code image |
| `environment` | varchar(255) | sandbox/production |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

### Querying Submissions

```php
use App\Models\ZatcaSubmission;

// All submissions
$all = ZatcaSubmission::all();

// By status
$success = ZatcaSubmission::where('status', 'success')->get();
$failed = ZatcaSubmission::where('status', 'failed')->get();

// By environment
$production = ZatcaSubmission::where('environment', 'production')->get();

// Recent submissions
$recent = ZatcaSubmission::orderBy('created_at', 'desc')->take(10)->get();
```

## 🛠️ Troubleshooting

### "Class not found" errors

```bash
composer dump-autoload
```

### OpenSSL not found

Enable OpenSSL extension in `php.ini`:
```ini
extension=openssl
```

### Storage permissions (Linux/Mac)

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### "Invalid OTP" in Production

- Use the exact OTP provided by ZATCA portal
- OTPs expire after a certain time
- Request a new OTP if expired

### Certificate validation failed

- Ensure you're using the production certificate, not sandbox
- Verify certificate hasn't expired
- Check that CSR was generated with correct organization details

### Invoice validation failed

- Verify all invoice data matches ZATCA requirements
- Check tax calculations are correct
- Ensure invoice type matches your registration

### Connection timeout

- Check firewall settings
- Verify ZATCA API is accessible from your server
- Check network connectivity

### QR code not opening

- SVG files: Open in a web browser
- Check file exists: Verify the file path
- Check permissions: Ensure file is readable

## 📚 Resources

### Official Documentation

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [ZATCA Portal](https://zatca.gov.sa/)
- [ZATCA E-Invoicing Guidelines](https://zatca.gov.sa/en/E-Invoicing/Introduction/Guidelines/Pages/default.aspx)

### Application Logs

- **Database**: `zatca_submissions` table
- **File logs**: `storage/logs/laravel.log`
- **Submission history**: Query `ZatcaSubmission` model

## 📝 Important Notes

1. **Sandbox vs Production**
   - Use `sandbox` for testing
   - Switch to `production` only when ready
   - Sandbox allows any OTP, production requires real OTP

2. **Organization Identifier**
   - Must be exactly 15 digits
   - Must start and end with the digit 3
   - Example: `300000000000003`

3. **Invoice Types**
   - **Standard Invoice**: Full invoice with all details (`invoice_type: 'standard'`)
   - **Simplified Invoice**: Simplified invoice format (`invoice_type: 'simplified'`)
   - Invoice 1 in sample data = Standard Invoice
   - Invoice 2 in sample data = Simplified Invoice
   - Both types can be generated, signed, and submitted to ZATCA

4. **File Locations**
   - All generated files: `storage/app/zatca/output/`
   - QR codes: `storage/app/zatca/qr-codes/`
   - Make sure directories exist and are writable

5. **Security**
   - Never commit `.env` file
   - Keep private keys secure
   - Use HTTPS in production
   - Regular backups of certificates

## 🎉 Success Indicators

You'll know everything is working when:

1. ✅ `php artisan list zatca` shows 7 commands
2. ✅ `php artisan zatca:generate-csr` creates CSR and private key files
3. ✅ All workflow commands complete without errors
4. ✅ Files appear in `storage/app/zatca/output/`
5. ✅ QR codes appear in `storage/app/zatca/qr-codes/`
6. ✅ Submissions are logged to database

## 🔗 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review error logs in `storage/logs/laravel.log`
3. Check database logs in `zatca_submissions` table
4. Refer to ZATCA official documentation
5. Contact ZATCA support through the portal

---

**Built with Laravel 11 and ZATCA E-Invoicing Integration**
