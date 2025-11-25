# ZATCA Laravel Project - Setup & Run Guide

## 📋 Prerequisites

Before running this project, ensure you have:

- **PHP >= 8.2** (with OpenSSL extension enabled)
- **Composer** (PHP package manager)
- **Node.js & NPM** (for frontend assets - optional)
- **OpenSSL** (for certificate generation)

## 🚀 Installation Steps

### 1. Install PHP Dependencies

```bash
composer install
```

This will install:
- Laravel 11 framework
- `sevaske/php-zatca-xml` (for invoice generation and signing)
- `sevaske/zatca-api` (for ZATCA API integration)
- All other required dependencies

### 2. Create Environment File

Create a `.env` file from the example (if it doesn't exist):

**Windows:**
```bash
copy .env.example .env
```

**Linux/Mac:**
```bash
cp .env.example .env
```

Or create it manually with these essential variables:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# ZATCA Configuration
ZATCA_ENVIRONMENT=sandbox
ZATCA_ORGANIZATION_IDENTIFIER=15 digits
ZATCA_ORGANIZATION_NAME=Company Name
ZATCA_ORGANIZATION_UNIT=Auction System
ZATCA_COMMON_NAME=Auction System
ZATCA_COUNTRY_CODE=SA
ZATCA_ADDRESS=Riyadh King Fahd Road
ZATCA_BUSINESS_CATEGORY=Online Auction and E-Commerce Services
ZATCA_BUILDING_NUMBER=8008
ZATCA_CITY_SUBDIVISION_NAME=Al Olaya
ZATCA_CITY=Riyadh
ZATCA_POSTAL_CODE=12345
ZATCA_REGISTRATION_NUMBER=10 digits
ZATCA_SOLUTION_NAME=Auction System
ZATCA_SOLUTION_MODEL=v1.0
ZATCA_DEVICE_SERIAL_NUMBER=serial number here
ZATCA_CURRENCY=SAR
ZATCA_INVOICE_TYPE=1100
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Configure ZATCA Settings

Edit your `.env` file and update the ZATCA configuration values with your actual organization details:

- `ZATCA_ORGANIZATION_IDENTIFIER`: Must be 15 digits, starting and ending with 3
- `ZATCA_ORGANIZATION_NAME`: Your company name
- `ZATCA_REGISTRATION_NUMBER`: 10 digits registration number
- `ZATCA_DEVICE_SERIAL_NUMBER`: Unique serial number for your device/solution
- `ZATCA_ENVIRONMENT`: Use `sandbox` for testing or `production` for live

### 5. (Optional) Install Node Dependencies

If you plan to use the frontend assets:

```bash
npm install
```

## 🎯 Running ZATCA Commands

The project includes 5 Artisan commands for the complete ZATCA invoice workflow:

### Command 1: Generate CSR and Private Key

Generate a Certificate Signing Request (CSR) and private key for ZATCA compliance:

```bash
php artisan zatca:generate-csr
```

**Output:**
- `storage/app/zatca/output/certificate.csr` - Certificate Signing Request
- `storage/app/zatca/output/private.pem` - Private key

**Note:** Make sure your ZATCA organization details are correctly configured in `.env` before running this.

### Command 2: Request Compliance Certificate

Request a compliance certificate from ZATCA using the generated CSR:

```bash
php artisan zatca:request-compliance-certificate
```

Or provide the OTP directly:

```bash
php artisan zatca:request-compliance-certificate 123123
```

**Note:** In sandbox environment, you can use any OTP (e.g., `123123`).

**Output:**
- `storage/app/zatca/output/ZATCA_certificate_data.json` - Contains certificate, secret, and requestId

### Command 3: Generate Invoice XML

Generate a ZATCA-compliant Simplified Invoice XML:

```bash
php artisan zatca:generate-invoice
```

**Output:**
- `storage/app/zatca/output/Simplified_Invoice.xml` - Unsigned invoice XML

**Note:** The invoice contains sample data. You may need to modify the command to use dynamic data from your application.

### Command 4: Sign Invoice

Sign the generated invoice XML with the compliance certificate:

```bash
php artisan zatca:sign-invoice
```

Or specify a different invoice file:

```bash
php artisan zatca:sign-invoice YourInvoice.xml
```

**Output:**
- `storage/app/zatca/output/Simplified_Invoice_signed.xml` - Signed invoice XML

### Command 5: Submit Invoice to ZATCA

Submit the signed invoice to ZATCA for compliance validation:

```bash
php artisan zatca:submit-invoice
```

Or specify a different signed invoice file:

```bash
php artisan zatca:submit-invoice YourInvoice_signed.xml
```

**Output:**
- Console output with compliance response from ZATCA

## 📁 Project Structure

```
zatca-laravel/
├── app/
│   └── Console/
│       └── Commands/
│           ├── ZatcaGenerateCsr.php              # Step 1: Generate CSR
│           ├── ZatcaRequestComplianceCertificate.php  # Step 2: Request certificate
│           ├── ZatcaGenerateInvoice.php          # Step 3: Generate invoice
│           ├── ZatcaSignInvoice.php              # Step 4: Sign invoice
│           └── ZatcaSubmitInvoice.php            # Step 5: Submit invoice
├── config/
│   └── zatca.php                                 # ZATCA configuration
├── storage/
│   └── app/
│       └── zatca/
│           └── output/                           # Generated files location
│               ├── certificate.csr
│               ├── private.pem
│               ├── ZATCA_certificate_data.json
│               ├── Simplified_Invoice.xml
│               └── Simplified_Invoice_signed.xml
└── .env                                          # Environment configuration
```

## 🔄 Complete Workflow

Here's the complete workflow to generate and submit a ZATCA invoice:

```bash
# Step 1: Generate CSR and private key
php artisan zatca:generate-csr

# Step 2: Request compliance certificate (use OTP from ZATCA portal)
php artisan zatca:request-compliance-certificate 123123

# Step 3: Generate invoice XML
php artisan zatca:generate-invoice

# Step 4: Sign the invoice
php artisan zatca:sign-invoice

# Step 5: Submit to ZATCA
php artisan zatca:submit-invoice
```

## 🌐 Running the Web Application

To run the Laravel web application:

```bash
php artisan serve
```

Then visit: `http://localhost:8000`

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

Or with PHPUnit:

```bash
vendor/bin/phpunit
```

## ⚠️ Troubleshooting

### Issue: "Class not found" errors

**Solution:** Run `composer dump-autoload` to regenerate the autoloader:

```bash
composer dump-autoload
```

### Issue: Storage directory permissions

**Solution:** Ensure storage directories are writable:

**Windows:** Usually not an issue, but check folder permissions if needed.

**Linux/Mac:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Issue: OpenSSL errors when generating CSR

**Solution:** Ensure OpenSSL extension is enabled in PHP:

1. Check `php.ini` file
2. Uncomment or add: `extension=openssl`
3. Restart your web server/PHP-FPM

### Issue: ZATCA API connection errors

**Solution:**
- Check your internet connection
- Verify `ZATCA_ENVIRONMENT` is set correctly (`sandbox` or `production`)
- Ensure you're using the correct OTP for your environment
- Check ZATCA API status

### Issue: Certificate/CSR file not found

**Solution:** Make sure you run the commands in order:
1. First generate CSR
2. Then request certificate
3. Then generate invoice
4. Then sign invoice
5. Finally submit invoice

## 📝 Important Notes

1. **Sandbox vs Production:**
   - Use `sandbox` environment for testing
   - Switch to `production` only when ready for live invoices
   - Sandbox allows any OTP, production requires real OTP from ZATCA portal

2. **Organization Identifier:**
   - Must be exactly 15 digits
   - Must start and end with the digit 3
   - Example: `300000000000003`

3. **Invoice Type:**
   - `1100` = Standard Invoice
   - `0100` = Simplified Invoice
   - The value is a 4-digit code where each digit acts as a boolean

4. **File Locations:**
   - All generated files are stored in `storage/app/zatca/output/`
   - Make sure this directory exists and is writable

## 🔗 Useful Commands

List all available ZATCA commands:

```bash
php artisan list zatca
```

Check Laravel version:

```bash
php artisan --version
```

Clear application cache:

```bash
php artisan cache:clear
php artisan config:clear
```

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [ZATCA Portal](https://zatca.gov.sa/)
- [ZATCA E-Invoicing Guidelines](https://zatca.gov.sa/en/E-Invoicing/Introduction/Guidelines/Pages/default.aspx)

## 🆘 Support

If you encounter issues:
1. Check the error messages in the console
2. Review the logs in `storage/logs/laravel.log`
3. Verify your `.env` configuration
4. Ensure all prerequisites are installed

