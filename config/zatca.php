<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZATCA Environment
    |--------------------------------------------------------------------------
    |
    | Options: 'sandbox', 'simulation', 'production'
    |
    */
    'environment' => env('ZATCA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Organization Details
    |--------------------------------------------------------------------------
    |
    | Your organization information for certificate generation
    |
    */
    'organization' => [
        'identifier' => env('ZATCA_ORG_IDENTIFIER', '399999999900003'),
        'name' => env('ZATCA_ORG_NAME', 'My Company'),
        'unit_name' => env('ZATCA_ORG_UNIT', 'IT Department'),
        'address' => env('ZATCA_ORG_ADDRESS', 'Riyadh, Saudi Arabia'),
        'country' => env('ZATCA_ORG_COUNTRY', 'SA'),
        'business_category' => env('ZATCA_BUSINESS_CATEGORY', 'Technology'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificate Details
    |--------------------------------------------------------------------------
    |
    | Certificate generation settings
    |
    */
    'certificate' => [
        'solution_name' => env('ZATCA_SOLUTION_NAME', 'Laravel ZATCA'),
        'model' => env('ZATCA_MODEL', 'Web'),
        'serial_number' => env('ZATCA_SERIAL_NUMBER', 'LARAVEL001'),
        'invoice_type' => env('ZATCA_INVOICE_TYPE', 1100), // 1100 = Standard + Simplified
        'production' => env('ZATCA_PRODUCTION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Paths relative to storage/app/
    |
    */
    'paths' => [
        'certificates' => 'zatca/certificates',
        'invoices' => 'zatca/invoices',
        'invoices_signed' => 'zatca/invoices/signed',
        'qr_codes' => 'zatca/qr_codes',
        'logs' => 'zatca/logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | ZATCA API configuration
    |
    */
    'api' => [
        'timeout' => env('ZATCA_API_TIMEOUT', 30),
        'retry_attempts' => env('ZATCA_API_RETRY', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Code
    |--------------------------------------------------------------------------
    |
    | OTP code for requesting compliance certificate from ZATCA
    | Get this from ZATCA portal after uploading CSR
    |
    */
    'otp' => env('ZATCA_OTP', null),
];

