<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZatcaSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'uuid',
        'invoice_hash',
        'status',
        'submission_type',
        'request_data',
        'response_data',
        'error_message',
        'zatca_request_id',
        'validation_status',
        'validation_errors',
        'invoice_file_path',
        'signed_invoice_file_path',
        'qr_code_path',
        'environment',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'validation_errors' => 'array',
    ];
}
