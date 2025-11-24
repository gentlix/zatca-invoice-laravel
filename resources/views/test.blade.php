<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZATCA Invoice System - Test Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
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
        .status-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .status-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-label {
            font-weight: 600;
            color: #333;
        }
        .status-value {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        .success-banner {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 1.2em;
            font-weight: 600;
        }
        .endpoints {
            margin-top: 30px;
            padding: 20px;
            background: #e7f3ff;
            border-radius: 10px;
            border-left: 4px solid #2196F3;
        }
        .endpoints h2 {
            color: #2196F3;
            margin-bottom: 15px;
        }
        .endpoint-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 3px solid #2196F3;
        }
        .endpoint-method {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85em;
            margin-right: 10px;
        }
        .method-get {
            background: #61affe;
            color: white;
        }
        .method-post {
            background: #49cc90;
            color: white;
        }
        .endpoint-url {
            font-family: 'Courier New', monospace;
            color: #333;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 ZATCA Invoice System</h1>
        <p class="subtitle">System Test & Status Page</p>

        <div class="success-banner">
            ✅ Laravel Application is Running Successfully!
        </div>

        <!-- Laravel Status -->
        <div class="status-section">
            <h2>📦 Laravel Status</h2>
            <div class="status-item">
                <span class="status-label">Laravel Framework</span>
                <span class="status-value status-ok">{{ $status['laravel'] }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">PHP Version</span>
                <span class="status-value status-ok">{{ $status['php_version'] }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Laravel Version</span>
                <span class="status-value status-ok">{{ $status['laravel_version'] }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Environment</span>
                <span class="status-value status-ok">{{ $status['environment'] }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Debug Mode</span>
                <span class="status-value {{ $status['debug_mode'] === 'Enabled' ? 'status-warning' : 'status-ok' }}">{{ $status['debug_mode'] }}</span>
            </div>
        </div>

        <!-- Database Status -->
        <div class="status-section">
            <h2>🗄️ Database Status</h2>
            <div class="status-item">
                <span class="status-label">Connection</span>
                <span class="status-value {{ str_contains($status['database'], '✅') ? 'status-ok' : 'status-error' }}">{{ $status['database'] }}</span>
            </div>
            @if(isset($status['database_name']))
            <div class="status-item">
                <span class="status-label">Database Name</span>
                <span class="status-value status-ok">{{ $status['database_name'] }}</span>
            </div>
            @endif
            @if(isset($status['database_error']))
            <div class="status-item">
                <span class="status-label">Error</span>
                <span class="status-value status-error">{{ $status['database_error'] }}</span>
            </div>
            @endif
        </div>

        <!-- Storage Status -->
        <div class="status-section">
            <h2>💾 Storage Status</h2>
            <div class="status-item">
                <span class="status-label">Storage System</span>
                <span class="status-value {{ str_contains($status['storage'], '✅') ? 'status-ok' : 'status-error' }}">{{ $status['storage'] }}</span>
            </div>
        </div>

        <!-- PHP Extensions -->
        <div class="status-section">
            <h2>🔌 PHP Extensions</h2>
            @foreach($status['php_extensions'] as $ext => $value)
            <div class="status-item">
                <span class="status-label">{{ $ext }}</span>
                <span class="status-value {{ str_contains($value, '✅') ? 'status-ok' : 'status-error' }}">{{ $value }}</span>
            </div>
            @endforeach
        </div>

        <!-- ZATCA Services -->
        <div class="status-section">
            <h2>⚙️ ZATCA Services</h2>
            @foreach($status['zatca_services'] as $service => $value)
            <div class="status-item">
                <span class="status-label">{{ $service }}</span>
                <span class="status-value {{ str_contains($value, '✅') ? 'status-ok' : 'status-error' }}">{{ $value }}</span>
            </div>
            @endforeach
        </div>

        <!-- Directories -->
        <div class="status-section">
            <h2>📁 Storage Directories</h2>
            @foreach($status['directories'] as $dir => $value)
            <div class="status-item">
                <span class="status-label">{{ $dir }}</span>
                <span class="status-value {{ str_contains($value, '✅') ? 'status-ok' : 'status-error' }}">{{ $value }}</span>
            </div>
            @endforeach
        </div>

        <!-- API Endpoints -->
        <div class="endpoints">
            <h2>📡 Available API Endpoints</h2>
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span class="endpoint-url">/</span>
                <p style="color: #666; margin-top: 5px; font-size: 0.9em;">API information</p>
            </div>
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span class="endpoint-url">/test</span>
                <p style="color: #666; margin-top: 5px; font-size: 0.9em;">This test page</p>
            </div>
            <div class="endpoint-item">
                <span class="endpoint-method method-post">POST</span>
                <span class="endpoint-url">/zatca/generate-csr</span>
                <p style="color: #666; margin-top: 5px; font-size: 0.9em;">Generate Certificate Signing Request</p>
            </div>
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span class="endpoint-url">/zatca/process-invoice-1</span>
                <p style="color: #666; margin-top: 5px; font-size: 0.9em;">Process Sample Invoice 1</p>
            </div>
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span class="endpoint-url">/zatca/process-invoice-2</span>
                <p style="color: #666; margin-top: 5px; font-size: 0.9em;">Process Sample Invoice 2</p>
            </div>
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span class="endpoint-url">/zatca/logs</span>
                <p style="color: #666; margin-top: 5px; font-size: 0.9em;">View submission logs</p>
            </div>
        </div>
    </div>
</body>
</html>

