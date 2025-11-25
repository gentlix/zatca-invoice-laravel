# ZATCA E-Invoicing Laravel Integration

A complete Laravel integration for ZATCA (Saudi Arabia) e-invoicing system. This project provides certificate management, invoice generation, signing, QR code generation, and API submission capabilities.

## Features

- Generate CSR and request ZATCA compliance certificate
- Create unsigned invoices (Simplified and Standard)
- Sign invoices with ZATCA certificate
- Generate QR codes for invoices
- Submit invoices to ZATCA API
- Log all API responses to database
- Clean generated files with a single command

## Requirements

- PHP >= 8.1
- Laravel >= 10.0
- Composer
- OpenSSL extension
- MySQL/MariaDB (for logging)

## Installation

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Copy environment file:**
   ```bash
   cp env.template .env
   ```

3. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

4. **Configure `.env` file:**
   - Update database credentials
   - Configure ZATCA settings (see Configuration section)

5. **Run migrations:**
   ```bash
   php artisan migrate
   ```

## Configuration

### Environment Variables

Edit your `.env` file with the following ZATCA settings:

```env
# ZATCA Environment: sandbox, simulation, or production
ZATCA_ENVIRONMENT=sandbox

# Organization Details
ZATCA_ORG_IDENTIFIER=399999999900003  # 15 digits, starting and ending with 3
ZATCA_ORG_NAME=My Company
ZATCA_ORG_UNIT=IT Department
ZATCA_ORG_ADDRESS=1234 Main St, Riyadh
ZATCA_ORG_COUNTRY=SA
ZATCA_BUSINESS_CATEGORY=Technology

# Certificate Details
ZATCA_SOLUTION_NAME=POS
ZATCA_MODEL=A1
ZATCA_SERIAL_NUMBER=98765
ZATCA_INVOICE_TYPE=1100  # 1000=Simplified, 1100=Standard+Simplified
ZATCA_PRODUCTION=false

# API Settings
ZATCA_API_TIMEOUT=30
ZATCA_API_RETRY=3

# OTP Code (optional, can be provided as command argument)
ZATCA_OTP=123123
```

### Configuration File

All ZATCA settings are managed in `config/zatca.php`. You can override these values using environment variables.

## Usage

### 1. Generate Certificate

Generate CSR and private key for ZATCA certificate:

```bash
php artisan zatca:generate-certificate
```

This creates:
- `storage/app/zatca/certificates/certificate.csr`
- `storage/app/zatca/certificates/private.pem`

**Next steps:**
1. Upload the CSR file to ZATCA portal
2. Get the OTP from ZATCA
3. Run the next command

### 2. Request Compliance Certificate

Request compliance certificate from ZATCA using the OTP:

```bash
php artisan zatca:request-compliance-certificate {otp}
```

Or set OTP in `.env` file and run without argument:

```bash
php artisan zatca:request-compliance-certificate
```

Replace `{otp}` with the OTP received from ZATCA portal.

This saves:
- `storage/app/zatca/certificates/ZATCA_certificate_data.json`

### 3. Generate Sample Invoices

Generate sample unsigned invoices:

```bash
php artisan zatca:generate-sample-invoices
```

This creates:
- Simplified Invoice (unsigned) - `storage/app/zatca/invoices/unsigned_Simplified_Invoice_*.xml`
- Standard Invoice (unsigned) - `storage/app/zatca/invoices/unsigned_Standard_Invoice_*.xml`

Note: Generated invoices are prefixed with `unsigned_` for easy identification.

### 4. Sign Invoices

Sign all unsigned invoices with ZATCA certificate:

```bash
php artisan zatca:sign-invoices
```

Or sign a specific invoice:

```bash
php artisan zatca:sign-invoices --file=Simplified_Invoice_*.xml
```

This creates:
- Signed invoices - `storage/app/zatca/invoices/signed/signed_*.xml`
- QR codes - `storage/app/zatca/qr_codes/signed_*.txt`

### 5. Submit Invoice to ZATCA

Submit a signed invoice to ZATCA API:

```bash
php artisan zatca:submit-invoice {invoice_uuid}
```

Replace `{invoice_uuid}` with the UUID of the invoice you want to submit.

The API response is automatically logged to the `zatca_logs` table.

### 6. Clean Generated Files

Clean generated files to free up storage:

```bash
# Clean only invoices
php artisan zatca:clean --invoices

# Clean only QR codes
php artisan zatca:clean --qr-codes

# Clean only certificates (with confirmation)
php artisan zatca:clean --certificates

# Clean everything
php artisan zatca:clean --all
```

## Available Commands

| Command | Description |
|---------|-------------|
| `zatca:generate-certificate` | Generate CSR and private key |
| `zatca:request-compliance-certificate {otp?}` | Request compliance certificate from ZATCA |
| `zatca:generate-sample-invoices` | Generate sample simplified and standard invoices |
| `zatca:sign-invoices {--file=}` | Sign unsigned invoices and generate QR codes |
| `zatca:submit-invoice {uuid}` | Submit signed invoice to ZATCA API |
| `zatca:clean {--invoices\|--qr-codes\|--certificates\|--all}` | Clean generated files |

## Project Structure

```
app/
├── Services/
│   ├── ZatcaCertificateService.php    # Certificate generation and management
│   ├── ZatcaInvoiceService.php        # Invoice generation from arrays
│   ├── ZatcaSigningService.php        # Invoice signing and QR code generation
│   └── ZatcaApiService.php            # ZATCA API integration
├── Models/
│   └── ZatcaLog.php                   # API response logging model
└── Console/Commands/
    ├── GenerateCertificate.php
    ├── RequestComplianceCertificate.php
    ├── GenerateSampleInvoices.php
    ├── SignInvoices.php
    ├── SubmitInvoice.php
    └── CleanZatcaFiles.php

config/
└── zatca.php                          # ZATCA configuration

database/migrations/
└── 2024_01_01_000001_create_zatca_logs_table.php

routes/
├── web.php                            # Web routes (default Laravel)
├── api.php                            # API routes (default Laravel)
└── console.php                        # Console routes
```

## File Storage

All ZATCA-related files are stored in `storage/app/zatca/`:

```
zatca/
├── certificates/
│   ├── certificate.csr                # Certificate Signing Request
│   ├── private.pem                    # Private key
│   └── ZATCA_certificate_data.json    # Certificate data from ZATCA
├── invoices/
│   ├── unsigned_Simplified_Invoice_*.xml  # Unsigned invoices
│   └── unsigned_Standard_Invoice_*.xml
├── invoices/signed/
│   ├── signed_Simplified_Invoice_*.xml    # Signed invoices
│   └── signed_Standard_Invoice_*.xml
└── qr_codes/
    ├── signed_Simplified_Invoice_*.txt  # QR codes (base64)
    └── signed_Standard_Invoice_*.txt
```

## Database Schema

### zatca_logs Table

Logs all API requests and responses:

- `id` - Primary key
- `endpoint` - API endpoint called
- `request_data` - JSON request data
- `response_data` - JSON response data
- `status` - Request status (success/error)
- `error_message` - Error message if failed
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Complete Workflow

Here's the complete workflow from certificate generation to invoice submission:

```bash
# Step 1: Generate certificate
php artisan zatca:generate-certificate

# Step 2: Upload CSR to ZATCA portal and get OTP
# (Manual step on ZATCA website)

# Step 3: Request compliance certificate
php artisan zatca:request-compliance-certificate {otp}

# Step 4: Generate invoices
php artisan zatca:generate-sample-invoices

# Step 5: Sign invoices
php artisan zatca:sign-invoices

# Step 6: Submit to ZATCA
php artisan zatca:submit-invoice {uuid}

# Optional: Clean generated files
php artisan zatca:clean --invoices --qr-codes
```

## Customizing Invoice Data

To customize invoice data, edit the methods in `app/Services/ZatcaInvoiceService.php`:

- `getSampleSimplifiedInvoice()` - Returns simplified invoice array
- `getSampleStandardInvoice()` - Returns standard invoice array

These methods return PHP arrays that are automatically converted to ZATCA-compliant XML.

## Production Setup

For production environment:

1. **Update `.env`:**
   ```env
   ZATCA_ENVIRONMENT=production
   ZATCA_PRODUCTION=true
   ```

2. **Update organization details** with your actual business information

3. **Generate production certificate:**
   - Use the same workflow but with production environment
   - Request production certificate from ZATCA portal

4. **Security considerations:**
   - Never commit `.env` file
   - Never commit certificate files or private keys
   - Use secure file permissions (600 for private keys)
   - Encrypt certificates at rest
   - Implement access controls

## Troubleshooting

### Certificate generation fails
- Check OpenSSL extension is enabled: `php -m | grep openssl`
- Verify write permissions on `storage/app/zatca/certificates/`
- On Windows, ensure OpenSSL is properly configured or set `OPENSSL_CONF` environment variable

### Signing fails
- Verify `ZATCA_certificate_data.json` exists in certificates directory
- Check certificate hasn't expired
- Verify invoice XML is valid
- Ensure private key file exists and is readable

### API submission fails
- Check network connectivity to ZATCA servers
- Verify environment settings (sandbox/simulation/production)
- Check certificate is valid and authentication is working
- Review API logs in `zatca_logs` table
- Review Laravel logs: `storage/logs/laravel.log`
- Verify invoice hash is correctly extracted from signed XML

### QR code not generated
- Verify certificate is valid and not expired
- Check invoice XML structure is correct
- Review signing service logs

### 401 Unauthorized errors
- Verify certificate format is correct (base64 encoded in JSON)
- Check certificate and secret are valid
- Ensure authentication header format is correct
- Verify environment matches certificate type (sandbox/simulation/production)

## API Logging

All API calls are automatically logged to the `zatca_logs` table. You can query them:

```php
use App\Models\ZatcaLog;

// Get all logs
$logs = ZatcaLog::all();

// Get failed submissions
$failed = ZatcaLog::where('status', 'error')->get();

// Get logs for specific endpoint
$certLogs = ZatcaLog::where('endpoint', 'request_compliance_certificate')->get();
```

## Security Best Practices

1. **Never commit sensitive files:**
   - `.env`
   - Certificate files (`*.pem`, `*.csr`, `*.json`)
   - Private keys

2. **File permissions:**
   ```bash
   chmod 600 storage/app/zatca/certificates/private.pem
   chmod 644 storage/app/zatca/certificates/certificate.csr
   ```

3. **Environment variables:**
   - Use different credentials for development and production
   - Rotate API secrets regularly
   - Use secure secret management

4. **Monitoring:**
   - Monitor API usage and failures
   - Set up alerts for certificate expiration
   - Track invoice submission success rates

## File Naming Conventions

- **Unsigned invoices:** Prefixed with `unsigned_` (e.g., `unsigned_Simplified_Invoice_20251124.xml`)
- **Signed invoices:** Prefixed with `signed_` (e.g., `signed_Simplified_Invoice_20251124.xml`)
- **QR codes:** Same name as signed invoice but with `.txt` extension

This naming convention makes it easy to identify file status and clean up specific file types.

## Next Steps

- Implement ICV (Invoice Counter) management
- Add PIH (Previous Invoice Hash) tracking
- Create web interface for invoice management
- Add invoice validation before submission
- Implement retry logic for failed submissions
- Set up monitoring and alerts
- Add unit tests

## Support

- **ZATCA Portal:** https://zatca.gov.sa
- **Package Documentation:** Check `vendor/saleh7/php-zatca-xml` for detailed API documentation

## License

This project uses Laravel framework and the `saleh7/php-zatca-xml` package. Please refer to their respective licenses.

