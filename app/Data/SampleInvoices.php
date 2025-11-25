<?php

namespace App\Data;

class SampleInvoices
{
    /**
     * Get sample invoice 1 data
     */
    public static function getInvoice1(): array
    {
        return [
            'invoice_id' => 'INV-001',
            'uuid' => null, // Will be auto-generated
            'issue_date' => now()->toDateTimeString(),
            'invoice_type' => 'simplified',
            'is_third_party' => false,
            'currency' => 'SAR',
            'tax_percent' => 15,
            'payment_means_code' => '10',
            
            'supplier' => [
                'name' => config('zatca.organization.name'),
                'tax_id' => config('zatca.organization.identifier'),
                'registration_number' => config('zatca.organization.registration_number'),
                'address' => [
                    'street' => config('zatca.organization.address'),
                    'building_number' => config('zatca.organization.building_number'),
                    'city_subdivision' => config('zatca.organization.city_subdivision_name'),
                    'city' => config('zatca.organization.city'),
                    'postal_code' => config('zatca.organization.postal_code'),
                    'country' => config('zatca.organization.country_code'),
                ],
            ],
            
            'customer' => [
                'name' => 'Ahmed Al-Saud',
                'tax_id' => null,
                'registration_number' => '1234567890',
                'address' => [
                    'street' => 'King Fahd Road',
                    'building_number' => '1234',
                    'city_subdivision' => 'Al Olaya',
                    'city' => 'Riyadh',
                    'postal_code' => '12211',
                    'country' => 'SA',
                ],
            ],
            
            'line_items' => [
                [
                    'name' => 'Product A',
                    'quantity' => 2,
                    'price' => 100.00,
                    'tax_percent' => 15,
                    'tax_amount' => 30.00,
                    'line_extension' => 200.00,
                    'total' => 230.00,
                    'unit_code' => 'PCE',
                ],
                [
                    'name' => 'Product B',
                    'quantity' => 1,
                    'price' => 150.00,
                    'tax_percent' => 15,
                    'tax_amount' => 22.50,
                    'line_extension' => 150.00,
                    'total' => 172.50,
                    'unit_code' => 'PCE',
                ],
            ],
            
            'taxable_amount' => 350.00,
            'tax_amount' => 52.50,
            
            'totals' => [
                'line_extension' => 350.00,
                'tax_exclusive' => 350.00,
                'tax_inclusive' => 402.50,
                'allowance' => 0,
                'prepaid' => 0,
                'payable' => 402.50,
            ],
            
            'icv' => '10',
            'pih' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
        ];
    }

    /**
     * Get sample invoice 2 data
     */
    public static function getInvoice2(): array
    {
        return [
            'invoice_id' => 'INV-002',
            'uuid' => null, // Will be auto-generated
            'issue_date' => now()->toDateTimeString(),
            'invoice_type' => 'simplified',
            'is_third_party' => false,
            'currency' => 'SAR',
            'tax_percent' => 15,
            'payment_means_code' => '10',
            
            'supplier' => [
                'name' => config('zatca.organization.name'),
                'tax_id' => config('zatca.organization.identifier'),
                'registration_number' => config('zatca.organization.registration_number'),
                'address' => [
                    'street' => config('zatca.organization.address'),
                    'building_number' => config('zatca.organization.building_number'),
                    'city_subdivision' => config('zatca.organization.city_subdivision_name'),
                    'city' => config('zatca.organization.city'),
                    'postal_code' => config('zatca.organization.postal_code'),
                    'country' => config('zatca.organization.country_code'),
                ],
            ],
            
            'customer' => [
                'name' => 'Fatima Al-Rashid',
                'tax_id' => null,
                'registration_number' => '9876543210',
                'address' => [
                    'street' => 'Prince Sultan Street',
                    'building_number' => '5678',
                    'city_subdivision' => 'Al Malaz',
                    'city' => 'Riyadh',
                    'postal_code' => '11564',
                    'country' => 'SA',
                ],
            ],
            
            'line_items' => [
                [
                    'name' => 'Service Fee',
                    'quantity' => 1,
                    'price' => 500.00,
                    'tax_percent' => 15,
                    'tax_amount' => 75.00,
                    'line_extension' => 500.00,
                    'total' => 575.00,
                    'unit_code' => 'C62', // Service unit
                ],
                [
                    'name' => 'Consultation',
                    'quantity' => 3,
                    'price' => 200.00,
                    'tax_percent' => 15,
                    'tax_amount' => 90.00,
                    'line_extension' => 600.00,
                    'total' => 690.00,
                    'unit_code' => 'HUR', // Hour
                ],
            ],
            
            'taxable_amount' => 1100.00,
            'tax_amount' => 165.00,
            
            'totals' => [
                'line_extension' => 1100.00,
                'tax_exclusive' => 1100.00,
                'tax_inclusive' => 1265.00,
                'allowance' => 0,
                'prepaid' => 0,
                'payable' => 1265.00,
            ],
            
            'icv' => '10',
            'pih' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
        ];
    }
}

