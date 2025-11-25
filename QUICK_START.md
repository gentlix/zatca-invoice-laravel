# 🚀 Quick Start Guide

## Prerequisites Check

Make sure you have:
- ✅ PHP 8.2+ installed
- ✅ Composer installed
- ✅ OpenSSL extension enabled in PHP

## Step-by-Step Setup

### 1. Install Dependencies

```bash
composer install
```

If you get errors, try:
```bash
composer update
```

### 2. Create .env File

**Windows PowerShell:**
```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

**Or manually create `.env` file** with at minimum:

```env
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
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Verify Installation

Check if commands are available:

```bash
php artisan list zatca
```

You should see 5 commands:
- `zatca:generate-csr`
- `zatca:request-compliance-certificate`
- `zatca:generate-invoice`
- `zatca:sign-invoice`
- `zatca:submit-invoice`

## 🎯 Run Your First ZATCA Workflow

### Complete Invoice Generation Flow:

```bash
# 1. Generate CSR (Certificate Signing Request)
php artisan zatca:generate-csr

# 2. Request Compliance Certificate (use any OTP in sandbox, e.g., 123123)
php artisan zatca:request-compliance-certificate 123123

# 3. Generate Invoice XML
php artisan zatca:generate-invoice

# 4. Sign the Invoice
php artisan zatca:sign-invoice

# 5. Submit to ZATCA
php artisan zatca:submit-invoice
```

## 📁 Output Files Location

All generated files will be in:
```
storage/app/zatca/output/
```

Files generated:
- `certificate.csr` - Certificate Signing Request
- `private.pem` - Private key
- `ZATCA_certificate_data.json` - Compliance certificate data
- `Simplified_Invoice.xml` - Unsigned invoice
- `Simplified_Invoice_signed.xml` - Signed invoice ready for submission

## ⚠️ Common Issues & Fixes

### "Class not found" Error
```bash
composer dump-autoload
```

### "Storage directory not writable"
The commands will create the directory automatically, but if you get errors:
- Windows: Check folder permissions
- Linux/Mac: `chmod -R 775 storage`

### "OpenSSL extension not found"
- Edit `php.ini`
- Uncomment: `extension=openssl`
- Restart PHP/web server

## 📝 Next Steps

1. **Customize Invoice Data**: Edit `app/Console/Commands/ZatcaGenerateInvoice.php` to use your actual invoice data
2. **Configure Production**: Update `.env` with production credentials when ready
3. **Integrate into Application**: Use the command logic in your controllers/services

## 🔍 Verify Everything Works

Run this test sequence:

```bash
# Test 1: Check Laravel is working
php artisan --version

# Test 2: Check ZATCA commands are registered
php artisan list zatca

# Test 3: Generate CSR (will create storage directory)
php artisan zatca:generate-csr

# Check if files were created
dir storage\app\zatca\output
```

If all steps complete without errors, you're ready to go! 🎉

