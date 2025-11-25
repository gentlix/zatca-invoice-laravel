# ZATCA Laravel Application

A Laravel 11 application with complete ZATCA (Saudi Arabia Tax Authority) e-invoicing integration.

## 📋 Requirements

- **PHP >= 8.2** (with OpenSSL extension)
- **Composer**
- **Node.js & NPM** (optional, for frontend assets)

## 🚀 Quick Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Create Environment File

**Windows:**
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

### 4. Configure ZATCA Settings

Edit `.env` and update with your actual ZATCA organization details.

## 🎯 Available Commands

This project includes 5 Artisan commands for the complete ZATCA workflow:

### 1. Generate CSR and Private Key
```bash
php artisan zatca:generate-csr
```
Generates Certificate Signing Request and private key for ZATCA compliance.

### 2. Request Compliance Certificate
```bash
php artisan zatca:request-compliance-certificate [otp]
```
Requests compliance certificate from ZATCA. In sandbox, you can use any OTP (e.g., `123123`).

### 3. Generate Invoice XML
```bash
php artisan zatca:generate-invoice
```
Generates a ZATCA-compliant Simplified Invoice XML.

### 4. Sign Invoice
```bash
php artisan zatca:sign-invoice [invoiceFile]
```
Signs the invoice XML with the compliance certificate.

### 5. Submit Invoice to ZATCA
```bash
php artisan zatca:submit-invoice [invoiceFile]
```
Submits the signed invoice to ZATCA for compliance validation.

## 📖 Complete Workflow Example

```bash
# Step 1: Generate CSR
php artisan zatca:generate-csr

# Step 2: Request certificate (use OTP from ZATCA portal, or any OTP in sandbox)
php artisan zatca:request-compliance-certificate 123123

# Step 3: Generate invoice
php artisan zatca:generate-invoice

# Step 4: Sign invoice
php artisan zatca:sign-invoice

# Step 5: Submit to ZATCA
php artisan zatca:submit-invoice
```

## 📁 Output Files

All generated files are stored in `storage/app/zatca/output/`:
- `certificate.csr` - Certificate Signing Request
- `private.pem` - Private key
- `ZATCA_certificate_data.json` - Compliance certificate data
- `Simplified_Invoice.xml` - Unsigned invoice
- `Simplified_Invoice_signed.xml` - Signed invoice

## ⚙️ Configuration

Configure ZATCA settings in `.env`:

```env
ZATCA_ENVIRONMENT=sandbox                    # or 'production'
ZATCA_ORGANIZATION_IDENTIFIER=300000000000003  # 15 digits, start/end with 3
ZATCA_ORGANIZATION_NAME=Your Company Name
ZATCA_REGISTRATION_NUMBER=1234567890          # 10 digits
ZATCA_DEVICE_SERIAL_NUMBER=DEVICE001
ZATCA_INVOICE_TYPE=1100                       # Invoice type code
```

See `config/zatca.php` for all available configuration options.

## 🌐 Running the Web Application

```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 📚 Documentation

- **Quick Start Guide**: See [QUICK_START.md](QUICK_START.md)
- **Detailed Setup**: See [SETUP.md](SETUP.md)

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
```

## 📦 Dependencies

- **Laravel 11** - PHP framework
- **sevaske/php-zatca-xml** - ZATCA XML invoice generation
- **sevaske/zatca-api** - ZATCA API integration (auto-installed as dependency)
- **Guzzle HTTP** - HTTP client for API requests

## 📝 Important Notes

1. **Sandbox vs Production**: Use `sandbox` for testing. Switch to `production` only when ready.
2. **Organization Identifier**: Must be exactly 15 digits, starting and ending with 3.
3. **OTP**: In sandbox, any OTP works. In production, use the OTP from ZATCA portal.

## 🔗 Resources

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [ZATCA Portal](https://zatca.gov.sa/)
- [ZATCA E-Invoicing Guidelines](https://zatca.gov.sa/en/E-Invoicing/Introduction/Guidelines/Pages/default.aspx)

