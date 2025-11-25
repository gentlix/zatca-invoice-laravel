<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZatcaLog extends Model
{
    protected $fillable = [
        'endpoint',
        'request_data',
        'response_data',
        'status',
        'error_message',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];
}

