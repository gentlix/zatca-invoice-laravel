# Project Status & How to Run

## ✅ What's Already Set Up

1. **Laravel 11 Framework** - Fully configured
2. **ZATCA Integration Packages** - Installed:
   - `sevaske/php-zatca-xml` (v3.4.1)
   - `sevaske/zatca-api` (v1.0.1) - auto-installed as dependency
3. **5 Artisan Commands** - Ready to use:
   - `zatca:generate-csr`
   - `zatca:request-compliance-certificate`
   - `zatca:generate-invoice`
   - `zatca:sign-invoice`
   - `zatca:submit-invoice`
4. **Configuration File** - `config/zatca.php` with all ZATCA settings
5. **Project Structure** - Complete Laravel structure with all directories

## ⚠️ What You Need to Do

### Step 1: Complete Composer Installation

The vendor folder exists but Laravel classes may not be fully loaded. Run:

```bash
composer install
```

If that doesn't work, try:
```bash
composer update
composer dump-autoload
```

### Step 2: Create .env File

The project needs a `.env` file. Create it with this minimum content:

```env
APP_NAME=ZATCA
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

ZATCA_ENVIRONMENT=sandbox
ZATCA_ORGANIZATION_IDENTIFIER=300000000000003
ZATCA_ORGANIZATION_NAME=Your Company Name
ZATCA_ORGANIZATION_UNIT=Auction System
ZATCA_COMMON_NAME=Auction System
ZATCA_COUNTRY_CODE=SA
ZATCA_ADDRESS=Riyadh King Fahd Road
ZATCA_BUSINESS_CATEGORY=Online Auction and E-Commerce Services
ZATCA_BUILDING_NUMBER=8008
ZATCA_CITY_SUBDIVISION_NAME=Al Olaya
ZATCA_CITY=Riyadh
ZATCA_POSTAL_CODE=12345
ZATCA_REGISTRATION_NUMBER=1234567890
ZATCA_SOLUTION_NAME=Auction System
ZATCA_SOLUTION_MODEL=v1.0
ZATCA_DEVICE_SERIAL_NUMBER=DEVICE001
ZATCA_CURRENCY=SAR
ZATCA_INVOICE_TYPE=1100
```

### Step 3: Generate Application Key

```bash
php artisan key:generate
```

### Step 4: Verify Installation

```bash
php artisan list zatca
```

You should see all 5 ZATCA commands listed.

## 🎯 How to Run the Complete Workflow

Once setup is complete, run these commands in order:

```bash
# 1. Generate Certificate Signing Request
php artisan zatca:generate-csr

# 2. Request Compliance Certificate (use any OTP in sandbox)
php artisan zatca:request-compliance-certificate 123123

# 3. Generate Invoice XML
php artisan zatca:generate-invoice

# 4. Sign the Invoice
php artisan zatca:sign-invoice

# 5. Submit to ZATCA
php artisan zatca:submit-invoice
```

## 📁 File Locations

- **Commands**: `app/Console/Commands/`
- **Config**: `config/zatca.php`
- **Output Files**: `storage/app/zatca/output/`
- **Logs**: `storage/logs/laravel.log`

## 🔍 Verification Checklist

Before running commands, verify:

- [ ] PHP 8.2+ is installed: `php -v`
- [ ] Composer is installed: `composer --version`
- [ ] OpenSSL extension enabled: `php -m | findstr openssl` (Windows) or `php -m | grep openssl` (Linux/Mac)
- [ ] `.env` file exists
- [ ] Application key is generated: `php artisan key:generate`
- [ ] Commands are registered: `php artisan list zatca`

## 🐛 Common Issues

### Issue: "Class Illuminate\Foundation\Application not found"

**Solution:**
```bash
composer install
composer dump-autoload
```

### Issue: "Storage directory not writable"

**Solution:** The commands automatically create the directory, but if you get errors:
- Windows: Check folder permissions
- Linux/Mac: `chmod -R 775 storage`

### Issue: "OpenSSL extension not found"

**Solution:**
1. Find your `php.ini` file: `php --ini`
2. Edit `php.ini` and uncomment: `extension=openssl`
3. Restart your terminal/web server

## 📚 Documentation Files

- **QUICK_START.md** - Fast setup guide
- **SETUP.md** - Detailed setup instructions
- **README.md** - Project overview

## ✨ Next Steps After Setup

1. **Customize Invoice Data**: Edit `app/Console/Commands/ZatcaGenerateInvoice.php` to use real invoice data
2. **Integrate into Application**: Use the command logic in your controllers
3. **Configure Production**: Update `.env` when ready for production
4. **Add Database**: If needed, run migrations and seeders

## 🎉 Success Indicators

You'll know everything is working when:

1. ✅ `php artisan list zatca` shows 5 commands
2. ✅ `php artisan zatca:generate-csr` creates CSR and private key files
3. ✅ All 5 workflow commands complete without errors
4. ✅ Files appear in `storage/app/zatca/output/`

