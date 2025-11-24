<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    /**
     * Simple test page to verify Laravel is working
     */
    public function index()
    {
        $status = [
            'laravel' => 'Working ✅',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
        ];

        // Test database connection
        try {
            DB::connection()->getPdo();
            $status['database'] = 'Connected ✅';
            $status['database_name'] = DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            $status['database'] = 'Not Connected ❌';
            $status['database_error'] = $e->getMessage();
        }

        // Test storage
        try {
            Storage::disk('local')->exists('test.txt');
            $status['storage'] = 'Working ✅';
        } catch (\Exception $e) {
            $status['storage'] = 'Error ❌';
        }

        // Test ZATCA services
        $status['zatca_services'] = [
            'CertificateService' => class_exists(\App\Services\Zatca\CertificateService::class) ? 'Available ✅' : 'Missing ❌',
            'XmlService' => class_exists(\App\Services\Zatca\XmlService::class) ? 'Available ✅' : 'Missing ❌',
            'QrCodeService' => class_exists(\App\Services\Zatca\QrCodeService::class) ? 'Available ✅' : 'Missing ❌',
            'ZatcaApiService' => class_exists(\App\Services\Zatca\ZatcaApiService::class) ? 'Available ✅' : 'Missing ❌',
        ];

        // Check extensions
        $status['php_extensions'] = [
            'openssl' => extension_loaded('openssl') ? 'Enabled ✅' : 'Disabled ❌',
            'zip' => extension_loaded('zip') ? 'Enabled ✅' : 'Disabled ❌',
            'gd' => extension_loaded('gd') ? 'Enabled ✅' : 'Disabled ❌',
            'pdo_mysql' => extension_loaded('pdo_mysql') ? 'Enabled ✅' : 'Disabled ❌',
        ];

        // Check directories
        $status['directories'] = [
            'certificates' => is_dir(storage_path('app/certificates')) ? 'Exists ✅' : 'Missing ❌',
            'qr_codes' => is_dir(storage_path('app/qr_codes')) ? 'Exists ✅' : 'Missing ❌',
            'invoices' => is_dir(storage_path('app/invoices')) ? 'Exists ✅' : 'Missing ❌',
        ];

        return view('test', compact('status'));
    }
}

