# ZATCA E-Invoicing Integration System

A complete Laravel-based system for integrating ZATCA (Saudi Arabia Tax Authority) Phase 2 e-invoicing requirements, including XML generation, certificate signing, QR code generation, and API submission.

## Features

- ✅ Generate Certificate Signing Request (CSR)
- ✅ Create unsigned and signed XML invoices (UBL 2.1 compliant)
- ✅ Sign invoices using CSID certificate
- ✅ Generate QR codes with TLV encoding
- ✅ Submit invoices to ZATCA Compliance API
- ✅ Comprehensive logging system
- ✅ Support for Sandbox and Production environments
- ✅ Two sample invoices for testing
- ✅ Interactive web test interface

## Requirements

- PHP 8.1 or higher
- Laravel 10.x
- MySQL/MariaDB
- OpenSSL extension
- Composer
- ZIP extension (for Composer)
- GD extension (optional, for QR codes)

## Installation

### Step 1: Install PHP Extensions

Open `C:\xampp\php\php.ini` (or your PHP ini file) and enable:

```ini
extension=openssl
extension=zip
extension=gd
extension=pdo_mysql
extension=mbstring
```

Restart your web server after making changes.

### Step 2: Install Dependencies

```bash
php composer.phar install --no-dev --ignore-platform-req=ext-gd
```

**Note:** Use `--ignore-platform-req=ext-gd` if GD extension is not enabled (QR codes won't work, but everything else will).

### Step 3: Environment Setup

Copy `.env.example` to `.env` (if not exists):

```bash
# Windows
copy .env.example .env

# Linux/Mac
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### Step 4: Configure Environment

Edit `.env` and update:

```env
APP_NAME="ZATCA Invoice System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zatca_invoice
DB_USERNAME=root
DB_PASSWORD=your_password

# ZATCA Configuration
ZATCA_ENVIRONMENT=sandbox
ZATCA_SANDBOX_URL=https://gw-apic-gov.gazt.gov.sa/e-invoicing/developer-portal
ZATCA_PRODUCTION_URL=https://gw-apic-gov.gazt.gov.sa/e-invoicing/core
ZATCA_CLIENT_ID=your_client_id
ZATCA_CLIENT_SECRET=your_client_secret
```

### Step 5: Create Database

```sql
CREATE DATABASE zatca_invoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 6: Run Migrations

```bash
php artisan migrate
```

### Step 7: Start Development Server

**Option 1: Laravel Artisan (Recommended)**
```bash
php artisan serve
```

**Option 2: PHP Built-in Server**
```bash
cd public
php -S localhost:8000
```

**Option 3: XAMPP/WAMP**
- Place project in `htdocs` folder
- Access via: `http://localhost/zatca_invoice/public`

Server will be available at: **http://localhost:8000**

## Quick Start Commands

```bash
# Install dependencies
php composer.phar install --no-dev --ignore-platform-req=ext-gd

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Start server
php artisan serve
```

## Usage

### Web Test Interface

Access the interactive test interface:
```
http://localhost:8000/test-page
```

This page allows you to:
- Generate CSR with a form
- Process sample invoices with buttons
- View submission logs in a table
- See real-time results

### API Endpoints

#### 1. Generate CSR

```bash
POST http://localhost:8000/zatca/generate-csr
Content-Type: application/json

{
    "name": "Your Company Name",
    "vat_number": "123456789100003",
    "city": "Riyadh",
    "state": "Riyadh",
    "email": "info@example.com"
}
```

**Response:**
- CSR content (submit to ZATCA portal)
- Private key path (already saved)
- Instructions for certificate installation

**Next Steps:**
1. Copy the CSR content
2. Submit it to ZATCA portal to get your certificate
3. Save certificate to: `storage/app/certificates/cert.pem`
4. Private key is already at: `storage/app/certificates/private_key.pem`

#### 2. Process Sample Invoice 1

```bash
GET http://localhost:8000/zatca/process-invoice-1
```

**What it does:**
1. Generates unsigned XML (UBL 2.1)
2. Signs XML with certificate
3. Generates QR code
4. Submits to ZATCA API
5. Logs response to database

**Response includes:**
- Invoice UUID
- Unsigned XML path
- Signed XML path
- QR code path
- ZATCA API submission result

#### 3. Process Sample Invoice 2

```bash
GET http://localhost:8000/zatca/process-invoice-2
```

Same process as Invoice 1, with different invoice data.

#### 4. View Logs

```bash
GET http://localhost:8000/zatca/logs
```

**Query Parameters:**
- `?status=success` - Filter by success
- `?status=error` - Filter by error
- `?invoice_uuid=xxx` - Filter by UUID

#### 5. Get Log Details

```bash
GET http://localhost:8000/zatca/logs/{id}
```

## Project Structure

```
zatca_invoice/
├── app/
│   ├── Data/
│   │   └── SampleInvoices.php          # Two sample invoices
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ZatcaController.php      # Main API controller
│   │   │   ├── TestController.php      # System status page
│   │   │   └── TestPageController.php   # Test interface controller
│   │   └── Middleware/                  # Laravel middleware
│   ├── Models/
│   │   └── ZatcaLog.php                 # Log model
│   └── Services/Zatca/
│       ├── CertificateService.php       # CSR & certificate management
│       ├── XmlService.php               # XML generation & signing
│       ├── QrCodeService.php            # QR code generation
│       └── ZatcaApiService.php          # ZATCA API integration
├── config/
│   ├── zatca.php                        # ZATCA configuration
│   └── [other Laravel configs]
├── database/
│   └── migrations/
│       └── create_zatca_logs_table.php  # Logs table migration
├── public/
│   └── index.php                        # Entry point
├── resources/
│   └── views/
│       ├── test.blade.php               # System status page
│       └── test-page.blade.php          # Test interface
├── routes/
│   └── web.php                          # API routes
└── storage/
    ├── app/
    │   ├── certificates/                # Certificate storage
    │   ├── invoices/                     # Invoice XML storage
    │   └── qr_codes/                     # QR code storage
    └── logs/                             # Application logs
```

## Sample Invoices

### Invoice 1 (INV-001)
- **Type**: Standard Invoice
- **Items**: 2 products
- **Subtotal**: 400.00 SAR
- **VAT**: 60.00 SAR (15%)
- **Total**: 460.00 SAR
- **Buyer**: Customer Name (Jeddah)

### Invoice 2 (INV-002)
- **Type**: Standard Invoice
- **Items**: 3 services
- **Subtotal**: 625.00 SAR
- **VAT**: 93.75 SAR (15%)
- **Total**: 718.75 SAR
- **Buyer**: Another Customer (Dammam)

## Configuration

### ZATCA Settings (config/zatca.php)

All settings can be overridden via `.env`:

- `ZATCA_ENVIRONMENT`: `sandbox` or `production`
- `ZATCA_SANDBOX_URL`: Sandbox API URL
- `ZATCA_PRODUCTION_URL`: Production API URL
- `ZATCA_CLIENT_ID`: API client ID
- `ZATCA_CLIENT_SECRET`: API client secret
- Certificate and key paths

## Invoice Processing Flow

```
1. Input: Invoice Data (PHP Array)
   ↓
2. Generate Unsigned XML (UBL 2.1)
   ├── Create XML structure
   ├── Add seller/buyer info
   ├── Add line items
   ├── Calculate totals
   └── Generate UUID
   ↓
3. Sign XML (PKI)
   ├── Load certificate
   ├── Create signature
   ├── Calculate hash
   └── Embed signature
   ↓
4. Generate QR Code
   ├── Encode TLV format
   ├── Include hash
   └── Generate PNG image
   ↓
5. Submit to ZATCA
   ├── Get OAuth token
   ├── POST signed XML
   ├── Receive response
   └── Log to database
   ↓
6. Output: Results
   ├── File paths
   ├── Submission status
   └── Log entry
```

## Database Schema

### zatca_logs Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `invoice_uuid` | string | Invoice UUID |
| `invoice_number` | string | Invoice number |
| `request_xml` | text | Full request XML |
| `response` | json | API response data |
| `status_code` | integer | HTTP status code |
| `status` | string | success/error/warning |
| `error_message` | text | Error message (if any) |
| `created_at` | timestamp | Creation time |
| `updated_at` | timestamp | Update time |

## Production Setup

### 1. Environment Configuration

Update `.env`:

```env
APP_ENV=production
APP_DEBUG=false
ZATCA_ENVIRONMENT=production
ZATCA_CLIENT_ID=your_production_client_id
ZATCA_CLIENT_SECRET=your_production_client_secret
```

### 2. Certificate Setup

1. Generate CSR using `/zatca/generate-csr` endpoint
2. Submit CSR to ZATCA portal
3. Download production certificate
4. Save to `storage/app/certificates/cert.pem`
5. Ensure private key is secure

### 3. Security Checklist

- [ ] Production certificates installed
- [ ] Private keys secured
- [ ] Environment variables configured
- [ ] Database credentials secure
- [ ] SSL certificate installed
- [ ] Error logging configured
- [ ] Backup strategy in place
- [ ] APP_DEBUG=false in production

## Troubleshooting

### Certificate Issues
- Verify certificate is in PEM format
- Check file permissions
- Ensure certificate matches private key
- Verify certificate hasn't expired

### API Connection Issues
- Verify API credentials in `.env`
- Check network connectivity
- Review error logs in database
- Test with sandbox first

### XML Validation Errors
- Ensure all required fields are present
- Verify UBL 2.1 schema compliance
- Check namespaces and encoding
- Validate against ZATCA schema

### Database Connection
- Verify credentials in `.env`
- Ensure MySQL is running
- Check database exists
- Verify user permissions

### Missing Extensions
- Check `php -m` for loaded extensions
- Enable in `php.ini`
- Restart web server

## Dependencies

- `laravel/framework`: ^10.10
- `ramsey/uuid`: ^4.7 (UUID generation)
- `simplesoftwareio/simple-qrcode`: ^4.2 (QR codes)
- `guzzlehttp/guzzle`: ^7.8 (HTTP client)

## API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | API information |
| GET | `/test` | System status page |
| GET | `/test-page` | Interactive test interface |
| POST | `/zatca/generate-csr` | Generate CSR |
| GET | `/zatca/process-invoice-1` | Process sample invoice 1 |
| GET | `/zatca/process-invoice-2` | Process sample invoice 2 |
| GET | `/zatca/logs` | View all logs |
| GET | `/zatca/logs/{id}` | Get log details |

## Web Pages

- **http://localhost:8000/** - API information (JSON)
- **http://localhost:8000/test** - System status page
- **http://localhost:8000/test-page** - Interactive test interface

## File Storage

- **Certificates**: `storage/app/certificates/`
- **Invoices**: `storage/app/invoices/`
- **QR Codes**: `storage/app/qr_codes/`
- **Logs**: `storage/logs/laravel.log`

## Security Notes

- Never commit `.env` file
- Keep certificates and private keys secure
- Use environment variables for sensitive data
- Implement proper access controls in production
- Certificates are excluded from git (.gitignore)

## Support

- **ZATCA Developer Portal**: https://zatca.gov.sa
- **Laravel Documentation**: https://laravel.com/docs
- **UBL 2.1 Specification**: OASIS standard

## License

MIT

## Version

1.0.0

---

**Quick Start**: Install dependencies → Generate key → Run migrations → Start server → Visit `/test-page` to test!
