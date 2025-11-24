<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZatcaController;

Route::get('/', function () {
    return response()->json([
        'message' => 'ZATCA E-Invoicing System API',
        'version' => '1.0.0',
        'endpoints' => [
            'POST /zatca/generate-csr' => 'Generate Certificate Signing Request',
            'POST /zatca/save-certificate' => 'Save certificate from ZATCA',
            'GET /zatca/process-invoice-1' => 'Process Sample Invoice 1',
            'GET /zatca/process-invoice-2' => 'Process Sample Invoice 2',
            'GET /zatca/logs' => 'View submission logs',
            'GET /zatca/logs/{id}' => 'Get log details',
        ]
    ]);
});

Route::get('/test', [App\Http\Controllers\TestController::class, 'index']);

Route::prefix('test-page')->group(function () {
    Route::get('/', [App\Http\Controllers\TestPageController::class, 'index']);
    Route::post('/generate-csr', [App\Http\Controllers\TestPageController::class, 'testGenerateCSR']);
    Route::get('/process-invoice-1', [App\Http\Controllers\TestPageController::class, 'testProcessInvoice1']);
    Route::get('/process-invoice-2', [App\Http\Controllers\TestPageController::class, 'testProcessInvoice2']);
    Route::get('/logs', [App\Http\Controllers\TestPageController::class, 'getLogs']);
});

Route::prefix('zatca')->group(function () {
    Route::post('/generate-csr', [ZatcaController::class, 'generateCSR']);
    Route::post('/save-certificate', [ZatcaController::class, 'saveCertificate']);
    Route::get('/process-invoice-1', [ZatcaController::class, 'processInvoice1']);
    Route::get('/process-invoice-2', [ZatcaController::class, 'processInvoice2']);
    Route::get('/logs', [ZatcaController::class, 'viewLogs']);
    Route::get('/logs/{id}', [ZatcaController::class, 'getLog']);
});

