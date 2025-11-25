# ZATCA Production Setup Instructions

This guide will help you connect your Laravel application to ZATCA's production environment.

## Prerequisites

Before connecting to production, ensure you have:

1. ✅ Registered your organization with ZATCA
2. ✅ Obtained your production credentials from ZATCA portal
3. ✅ Completed ZATCA onboarding process
4. ✅ Received your production OTP (One-Time Password) from ZATCA

## Step 1: Update Environment Configuration

Edit your `.env` file and change the following settings:

```env
# Change from sandbox to production
ZATCA_ENVIRONMENT=production

# Update with your actual production organization details
ZATCA_ORGANIZATION_IDENTIFIER=your_15_digit_identifier
ZATCA_ORGANIZATION_NAME=Your Actual Company Name
ZATCA_REGISTRATION_NUMBER=your_10_digit_registration
ZATCA_DEVICE_SERIAL_NUMBER=your_unique_device_serial

# Update address and location details
ZATCA_ADDRESS=Your Actual Street Address
ZATCA_BUILDING_NUMBER=Your Building Number
ZATCA_CITY_SUBDIVISION_NAME=Your City Subdivision
ZATCA_CITY=Your City
ZATCA_POSTAL_CODE=Your Postal Code
```

## Step 2: Generate Production CSR

Generate a new Certificate Signing Request (CSR) with your production details:

```bash
php artisan zatca:generate-csr
```

This will create:
- `storage/app/zatca/output/certificate.csr`
- `storage/app/zatca/output/private.pem`

**Important:** Keep your private key secure! Never share it or commit it to version control.

## Step 3: Request Production Compliance Certificate

1. Log in to the ZATCA portal: https://zatca.gov.sa/
2. Navigate to the Certificate section
3. Upload your `certificate.csr` file
4. You will receive an OTP via email/SMS

Request the compliance certificate using the OTP:

```bash
php artisan zatca:request-compliance-certificate YOUR_PRODUCTION_OTP
```

**Note:** In production, you must use the actual OTP provided by ZATCA. Unlike sandbox, you cannot use any random OTP.

## Step 4: Verify Production Connection

Test your production connection by generating and submitting a test invoice:

```bash
# Generate invoice from array
php artisan zatca:generate-invoice-from-array 1

# Sign the invoice
php artisan zatca:sign-invoice Invoice_INV-001.xml

# Submit to production (this will use production environment)
php artisan zatca:submit-invoice Invoice_INV-001_signed.xml
```

## Step 5: Database Configuration

Ensure your database is configured for production:

```env
DB_CONNECTION=mysql
DB_HOST=your_production_db_host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

Run migrations to create the logging table:

```bash
php artisan migrate
```

## Step 6: Security Considerations

### 1. Secure Storage

- Store private keys and certificates securely
- Use environment variables for sensitive data
- Never commit `.env` file to version control
- Use secure file permissions (600) for certificate files

### 2. SSL/TLS

- Ensure your application uses HTTPS in production
- ZATCA API requires secure connections

### 3. Backup

- Regularly backup your certificates and private keys
- Store backups in secure, encrypted locations
- Keep multiple copies in different secure locations

## Step 7: Monitoring and Logging

### View Submission Logs

Check the database for submission history:

```php
use App\Models\ZatcaSubmission;

// Get all submissions
$submissions = ZatcaSubmission::all();

// Get failed submissions
$failed = ZatcaSubmission::where('status', 'failed')->get();

// Get production submissions
$production = ZatcaSubmission::where('environment', 'production')->get();
```

### Log Files

Check Laravel logs for detailed error information:

```bash
tail -f storage/logs/laravel.log
```

## Step 8: Production Checklist

Before going live, verify:

- [ ] Environment set to `production` in `.env`
- [ ] All organization details are correct
- [ ] Production CSR generated
- [ ] Production compliance certificate obtained
- [ ] Database configured and migrations run
- [ ] Test invoice submitted successfully
- [ ] QR codes are being generated correctly
- [ ] Submission logs are being saved
- [ ] Error handling is working
- [ ] SSL/TLS is configured
- [ ] Backups are in place

## Step 9: API Endpoints

### Sandbox Endpoints
- Base URL: `https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal/`
- Used for: Testing and development

### Production Endpoints
- Base URL: `https://gw-fatoora.zatca.gov.sa/e-invoicing/core/`
- Used for: Live invoice submissions

The application automatically uses the correct endpoint based on `ZATCA_ENVIRONMENT` setting.

## Step 10: Common Production Issues

### Issue: "Invalid OTP"
**Solution:** Use the exact OTP provided by ZATCA portal. OTPs expire after a certain time.

### Issue: "Certificate validation failed"
**Solution:** 
- Ensure you're using the production certificate, not sandbox
- Verify certificate hasn't expired
- Check that CSR was generated with correct organization details

### Issue: "Invoice validation failed"
**Solution:**
- Verify all invoice data matches ZATCA requirements
- Check tax calculations are correct
- Ensure invoice type matches your registration

### Issue: "Connection timeout"
**Solution:**
- Check firewall settings
- Verify ZATCA API is accessible from your server
- Check network connectivity

## Step 11: Support and Resources

### ZATCA Resources
- Portal: https://zatca.gov.sa/
- Documentation: https://zatca.gov.sa/en/E-Invoicing/Introduction/Guidelines/Pages/default.aspx
- Support: Contact ZATCA support through the portal

### Application Logs
- Database: `zatca_submissions` table
- File logs: `storage/logs/laravel.log`
- Submission history: Query `ZatcaSubmission` model

## Step 12: Maintenance

### Certificate Renewal
Certificates expire periodically. Before expiration:
1. Generate new CSR
2. Request new certificate from ZATCA
3. Update certificate files
4. Test with a sample invoice

### Regular Monitoring
- Check submission logs daily
- Monitor for failed submissions
- Review error messages
- Keep certificates up to date

## Important Notes

1. **Never use sandbox certificates in production**
2. **Keep private keys secure and backed up**
3. **Monitor submissions regularly**
4. **Test thoroughly before going live**
5. **Follow ZATCA guidelines strictly**

## Testing in Production

After setup, test with a small invoice first:
1. Generate a test invoice
2. Sign it
3. Submit to production
4. Verify response in database
5. Check ZATCA portal for invoice status

Once verified, you can start processing real invoices.

