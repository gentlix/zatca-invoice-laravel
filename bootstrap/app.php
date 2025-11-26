<?php

use App\Console\Commands\ZatcaCleanHistory;
use App\Console\Commands\ZatcaGenerateCsr;
use App\Console\Commands\ZatcaGenerateInvoice;
use App\Console\Commands\ZatcaGenerateInvoiceFromArray;
use App\Console\Commands\ZatcaRequestComplianceCertificate;
use App\Console\Commands\ZatcaSignInvoice;
use App\Console\Commands\ZatcaSubmitInvoice;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ZatcaCleanHistory::class,
        ZatcaGenerateCsr::class,
        ZatcaGenerateInvoice::class,
        ZatcaGenerateInvoiceFromArray::class,
        ZatcaRequestComplianceCertificate::class,
        ZatcaSignInvoice::class,
        ZatcaSubmitInvoice::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

