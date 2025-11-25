<?php

use Illuminate\Support\Env;

return [

    /*
    |--------------------------------------------------------------------------
    | ZATCA Environment
    |--------------------------------------------------------------------------
    |
    | The environment for ZATCA integration. Options: 'sandbox', 'production'
    |
    */

    'environment' => Env::get('ZATCA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Organization Information
    |--------------------------------------------------------------------------
    |
    | Your organization details as registered with ZATCA
    |
    */

    'organization' => [
        'identifier' => Env::get('ZATCA_ORGANIZATION_IDENTIFIER', '15 digits'),
        'name' => Env::get('ZATCA_ORGANIZATION_NAME', 'Company Name'),
        'unit' => Env::get('ZATCA_ORGANIZATION_UNIT', 'Auction System'),
        'common_name' => Env::get('ZATCA_COMMON_NAME', 'Auction System'),
        'country_code' => Env::get('ZATCA_COUNTRY_CODE', 'SA'),
        'address' => Env::get('ZATCA_ADDRESS', 'Riyadh King Fahd Road'),
        'business_category' => Env::get('ZATCA_BUSINESS_CATEGORY', 'Online Auction and E-Commerce Services'),
        'building_number' => Env::get('ZATCA_BUILDING_NUMBER', '8008'),
        'city_subdivision_name' => Env::get('ZATCA_CITY_SUBDIVISION_NAME', 'Al Olaya'),
        'city' => Env::get('ZATCA_CITY', 'Riyadh'),
        'postal_code' => Env::get('ZATCA_POSTAL_CODE', '12345'),
        'registration_number' => Env::get('ZATCA_REGISTRATION_NUMBER', '10 digits'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device/Solution Information
    |--------------------------------------------------------------------------
    |
    | Information about your solution/device
    |
    */

    'device' => [
        'solution_name' => Env::get('ZATCA_SOLUTION_NAME', 'Auction System'),
        'model' => Env::get('ZATCA_SOLUTION_MODEL', 'v1.0'),
        'serial_number' => Env::get('ZATCA_DEVICE_SERIAL_NUMBER', 'serial number here'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    'is_production' => Env::get('ZATCA_ENVIRONMENT', 'sandbox') === 'production',
    'currency' => Env::get('ZATCA_CURRENCY', 'SAR'),
    'invoice_type' => Env::get('ZATCA_INVOICE_TYPE', '1100'),
];
