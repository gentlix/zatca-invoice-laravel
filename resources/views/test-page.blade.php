<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ZATCA Invoice System - Test Interface</title>
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
            max-width: 1200px;
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
        }
        .test-section {
            margin: 30px 0;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .test-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        textarea {
            min-height: 100px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .result-box {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
            max-height: 400px;
            overflow-y: auto;
        }
        .result-box pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 12px;
        }
        .result-success {
            border-left: 4px solid #28a745;
        }
        .result-error {
            border-left: 4px solid #dc3545;
        }
        .loading {
            display: none;
            color: #667eea;
            font-weight: 600;
        }
        .loading.show {
            display: inline-block;
        }
        .invoice-preview {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .invoice-preview h3 {
            color: #2196F3;
            margin-bottom: 10px;
        }
        .invoice-preview p {
            margin: 5px 0;
            color: #666;
        }
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .logs-table th,
        .logs-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .logs-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 ZATCA Invoice System</h1>
        <p class="subtitle">Interactive Test Interface - Test All Endpoints</p>

        <!-- Generate CSR Section -->
        <div class="test-section">
            <h2>1. Generate Certificate Signing Request (CSR)</h2>
            <form id="csrForm">
                <div class="form-group">
                    <label>Company Name:</label>
                    <input type="text" name="name" value="Test Company Ltd" required>
                </div>
                <div class="form-group">
                    <label>VAT Registration Number:</label>
                    <input type="text" name="vat_number" value="123456789100003" required>
                </div>
                <div class="form-group">
                    <label>City:</label>
                    <input type="text" name="city" value="Riyadh" required>
                </div>
                <div class="form-group">
                    <label>State:</label>
                    <input type="text" name="state" value="Riyadh" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="info@example.com" required>
                </div>
                <button type="submit" class="btn">Generate CSR</button>
                <span class="loading" id="csrLoading">Generating...</span>
            </form>
            <div class="result-box" id="csrResult" style="display: none;"></div>
        </div>

        <!-- Save Certificate Section -->
        <div class="test-section">
            <h2>2. Save Certificate from ZATCA</h2>
            <p style="margin-bottom: 15px; color: #666;">
                After submitting your CSR to ZATCA portal and receiving the certificate, paste it here to save it.
            </p>
            <form id="certificateForm">
                <div class="form-group">
                    <label>Certificate (PEM Format):</label>
                    <textarea 
                        name="certificate" 
                        placeholder="-----BEGIN CERTIFICATE-----
MIIF...
-----END CERTIFICATE-----"
                        required
                        rows="10"
                    ></textarea>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Paste the full certificate content including BEGIN and END lines
                    </small>
                </div>
                <button type="submit" class="btn btn-success">Save Certificate</button>
                <span class="loading" id="certLoading">Saving...</span>
            </form>
            <div class="result-box" id="certResult" style="display: none;"></div>
        </div>

        <!-- Process Invoice 1 -->
        <div class="test-section">
            <h2>3. Process Sample Invoice 1</h2>
            <div class="invoice-preview">
                <h3>Invoice Details:</h3>
                <p><strong>Invoice Number:</strong> INV-001</p>
                <p><strong>Items:</strong> 2 products</p>
                <p><strong>Total:</strong> 460.00 SAR</p>
                <p><strong>Buyer:</strong> Customer Name (Jeddah)</p>
            </div>
            <button onclick="processInvoice(1)" class="btn btn-success">Process Invoice 1</button>
            <span class="loading" id="inv1Loading">Processing...</span>
            <div class="result-box" id="inv1Result" style="display: none;"></div>
        </div>

        <!-- Process Invoice 2 -->
        <div class="test-section">
            <h2>4. Process Sample Invoice 2</h2>
            <div class="invoice-preview">
                <h3>Invoice Details:</h3>
                <p><strong>Invoice Number:</strong> INV-002</p>
                <p><strong>Items:</strong> 3 services</p>
                <p><strong>Total:</strong> 718.75 SAR</p>
                <p><strong>Buyer:</strong> Another Customer (Dammam)</p>
            </div>
            <button onclick="processInvoice(2)" class="btn btn-success">Process Invoice 2</button>
            <span class="loading" id="inv2Loading">Processing...</span>
            <div class="result-box" id="inv2Result" style="display: none;"></div>
        </div>

        <!-- View Logs -->
        <div class="test-section">
            <h2>5. View Submission Logs</h2>
            <button onclick="loadLogs()" class="btn btn-secondary">Load Logs</button>
            <span class="loading" id="logsLoading">Loading...</span>
            <div id="logsResult" style="display: none;">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Invoice UUID</th>
                            <th>Invoice Number</th>
                            <th>Status</th>
                            <th>Status Code</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Setup CSRF token for all requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Generate CSR
        document.getElementById('csrForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            const loading = document.getElementById('csrLoading');
            const result = document.getElementById('csrResult');
            
            loading.classList.add('show');
            result.style.display = 'none';
            
            try {
                const response = await fetch('/test-page/generate-csr', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const json = await response.json();
                result.style.display = 'block';
                result.className = 'result-box ' + (json.success ? 'result-success' : 'result-error');
                result.innerHTML = '<pre>' + JSON.stringify(json, null, 2) + '</pre>';
            } catch (error) {
                result.style.display = 'block';
                result.className = 'result-box result-error';
                result.innerHTML = '<pre>Error: ' + error.message + '</pre>';
            } finally {
                loading.classList.remove('show');
            }
        });

        // Save Certificate
        document.getElementById('certificateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const certificate = formData.get('certificate').trim();
            
            if (!certificate) {
                alert('Please enter the certificate content');
                return;
            }
            
            const loading = document.getElementById('certLoading');
            const result = document.getElementById('certResult');
            
            loading.classList.add('show');
            result.style.display = 'none';
            
            try {
                const response = await fetch('/zatca/save-certificate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ certificate: certificate })
                });
                
                const json = await response.json();
                result.style.display = 'block';
                result.className = 'result-box ' + (json.success ? 'result-success' : 'result-error');
                
                if (json.success) {
                    let html = '<pre>' + JSON.stringify(json, null, 2) + '</pre>';
                    if (json.instructions) {
                        html += '<div style="margin-top: 15px; padding: 10px; background: #d4edda; border-radius: 5px;">';
                        html += '<strong>✅ Next Steps:</strong><ul style="margin: 10px 0 0 20px;">';
                        json.instructions.forEach(instruction => {
                            html += '<li>' + instruction + '</li>';
                        });
                        html += '</ul></div>';
                    }
                    result.innerHTML = html;
                } else {
                    result.innerHTML = '<pre>' + JSON.stringify(json, null, 2) + '</pre>';
                }
            } catch (error) {
                result.style.display = 'block';
                result.className = 'result-box result-error';
                result.innerHTML = '<pre>Error: ' + error.message + '</pre>';
            } finally {
                loading.classList.remove('show');
            }
        });

        // Process Invoice
        async function processInvoice(invoiceNumber) {
            const loading = document.getElementById('inv' + invoiceNumber + 'Loading');
            const result = document.getElementById('inv' + invoiceNumber + 'Result');
            
            loading.classList.add('show');
            result.style.display = 'none';
            
            try {
                const response = await fetch('/test-page/process-invoice-' + invoiceNumber, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const json = await response.json();
                result.style.display = 'block';
                result.className = 'result-box ' + (json.success ? 'result-success' : 'result-error');
                result.innerHTML = '<pre>' + JSON.stringify(json, null, 2) + '</pre>';
            } catch (error) {
                result.style.display = 'block';
                result.className = 'result-box result-error';
                result.innerHTML = '<pre>Error: ' + error.message + '</pre>';
            } finally {
                loading.classList.remove('show');
            }
        }

        // Load Logs
        async function loadLogs() {
            const loading = document.getElementById('logsLoading');
            const result = document.getElementById('logsResult');
            const tbody = document.getElementById('logsTableBody');
            
            loading.classList.add('show');
            result.style.display = 'none';
            
            try {
                const response = await fetch('/test-page/logs', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const logs = await response.json();
                result.style.display = 'block';
                
                tbody.innerHTML = '';
                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No logs found</td></tr>';
                } else {
                    logs.forEach(log => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${log.id}</td>
                            <td>${log.invoice_uuid || '-'}</td>
                            <td>${log.invoice_number || '-'}</td>
                            <td><span class="status-badge status-${log.status || 'error'}">${log.status || 'N/A'}</span></td>
                            <td>${log.status_code || '-'}</td>
                            <td>${new Date(log.created_at).toLocaleString()}</td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            } catch (error) {
                result.style.display = 'block';
                result.innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
            } finally {
                loading.classList.remove('show');
            }
        }
    </script>
</body>
</html>

