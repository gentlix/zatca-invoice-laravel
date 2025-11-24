<?php
/**
 * ZATCA Invoice System - Entry Point
 * This file works both with and without Laravel
 */

// Check if Laravel is installed
if (file_exists(__DIR__ . '/../vendor/laravel/framework')) {
    // Laravel is installed - use Laravel bootstrap
    define('LARAVEL_START', microtime(true));
    
    // Determine if the application is in maintenance mode...
    if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
        require $maintenance;
    }
    
    // Register the Composer autoloader...
    require __DIR__.'/../vendor/autoload.php';
    
    // Bootstrap Laravel and handle the request...
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    
    $response->send();
    
    $kernel->terminate($request, $response);
} else {
    // Laravel not installed - show welcome page with link to status
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ZATCA Invoice System</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
                padding: 40px;
                text-align: center;
            }
            h1 {
                color: #667eea;
                margin-bottom: 10px;
                font-size: 2.5em;
            }
            .subtitle {
                color: #666;
                margin-bottom: 30px;
                font-size: 1.1em;
            }
            .btn {
                display: inline-block;
                padding: 15px 30px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin: 10px;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #5568d3;
            }
            .info {
                margin-top: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚀 ZATCA Invoice System</h1>
            <p class="subtitle">E-Invoicing Integration Platform</p>
            
            <div class="info">
                <p><strong>Laravel dependencies not installed yet.</strong></p>
                <p style="margin-top: 10px;">To install, run:</p>
                <code style="background: #e9ecef; padding: 5px 10px; border-radius: 4px; display: inline-block; margin-top: 10px;">
                    php composer.phar install --no-dev
                </code>
                <p style="margin-top: 15px;">After installation, visit <code>/test</code> for system status.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
