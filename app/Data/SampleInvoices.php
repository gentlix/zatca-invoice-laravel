<?php

namespace App\Data;

class SampleInvoices
{
    /**
     * Get first sample invoice
     */
    public static function getInvoice1(): array
    {
        return [
            'invoice_number' => 'INV-001',
            'invoice_date' => '2024-01-15',
            'invoice_time' => '10:30:00',
            'invoice_type' => '388',
            'invoice_type_code' => '0100000',
            'currency' => 'SAR',
            
            'seller' => [
                'name' => 'Test Company Ltd',
                'vat_registration_number' => '123456789100003',
                'address' => [
                    'street' => 'King Fahd Road',
                    'building_number' => '1234',
                    'city' => 'Riyadh',
                    'postal_code' => '12345',
                    'country' => 'SA'
                ]
            ],
            
            'buyer' => [
                'name' => 'Customer Name',
                'vat_registration_number' => '987654321100003',
                'address' => [
                    'street' => 'Main Street',
                    'building_number' => '567',
                    'city' => 'Jeddah',
                    'postal_code' => '21432',
                    'country' => 'SA'
                ]
            ],
            
            'items' => [
                [
                    'name' => 'Product 1',
                    'quantity' => 2,
                    'unit_price' => 100.00,
                    'tax_rate' => 15,
                    'tax_amount' => 30.00,
                    'total' => 230.00
                ],
                [
                    'name' => 'Product 2',
                    'quantity' => 1,
                    'unit_price' => 200.00,
                    'tax_rate' => 15,
                    'tax_amount' => 30.00,
                    'total' => 230.00
                ]
            ],
            
            'totals' => [
                'subtotal' => 400.00,
                'vat_total' => 60.00,
                'total' => 460.00
            ]
        ];
    }

    /**
     * Get second sample invoice
     */
    public static function getInvoice2(): array
    {
        return [
            'invoice_number' => 'INV-002',
            'invoice_date' => '2024-01-16',
            'invoice_time' => '14:20:00',
            'invoice_type' => '388',
            'invoice_type_code' => '0100000',
            'currency' => 'SAR',
            
            'seller' => [
                'name' => 'Test Company Ltd',
                'vat_registration_number' => '123456789100003',
                'address' => [
                    'street' => 'King Fahd Road',
                    'building_number' => '1234',
                    'city' => 'Riyadh',
                    'postal_code' => '12345',
                    'country' => 'SA'
                ]
            ],
            
            'buyer' => [
                'name' => 'Another Customer',
                'vat_registration_number' => '111222333100003',
                'address' => [
                    'street' => 'Business District',
                    'building_number' => '789',
                    'city' => 'Dammam',
                    'postal_code' => '32245',
                    'country' => 'SA'
                ]
            ],
            
            'items' => [
                [
                    'name' => 'Service 1',
                    'quantity' => 5,
                    'unit_price' => 50.00,
                    'tax_rate' => 15,
                    'tax_amount' => 37.50,
                    'total' => 287.50
                ],
                [
                    'name' => 'Service 2',
                    'quantity' => 3,
                    'unit_price' => 75.00,
                    'tax_rate' => 15,
                    'tax_amount' => 33.75,
                    'total' => 258.75
                ],
                [
                    'name' => 'Service 3',
                    'quantity' => 1,
                    'unit_price' => 150.00,
                    'tax_rate' => 15,
                    'tax_amount' => 22.50,
                    'total' => 172.50
                ]
            ],
            
            'totals' => [
                'subtotal' => 625.00,
                'vat_total' => 93.75,
                'total' => 718.75
            ]
        ];
    }
}

