<?php

return [
    'environment' => env('ZATCA_ENVIRONMENT', 'sandbox'),
    
    'sandbox_url' => env('ZATCA_SANDBOX_URL', 'https://gw-apic-gov.gazt.gov.sa/e-invoicing/developer-portal'),
    'production_url' => env('ZATCA_PRODUCTION_URL', 'https://gw-apic-gov.gazt.gov.sa/e-invoicing/core'),
    
    'client_id' => env('ZATCA_CLIENT_ID'),
    'client_secret' => env('ZATCA_CLIENT_SECRET'),
    
    'certificate_path' => storage_path('app/certificates/cert.pem'),
    'private_key_path' => storage_path('app/certificates/private_key.pem'),
    'csr_path' => storage_path('app/certificates/csr.pem'),
    'qr_code_path' => storage_path('app/qr_codes'),
    
    'invoice_storage_path' => storage_path('app/invoices'),
];

