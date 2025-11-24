<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZatcaLog extends Model
{
    protected $fillable = [
        'invoice_uuid',
        'invoice_number',
        'request_xml',
        'response',
        'status_code',
        'status',
        'error_message',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    /**
     * Get the status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'success' => 'green',
            'error' => 'red',
            'warning' => 'yellow',
            default => 'gray',
        };
    }
}

