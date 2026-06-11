<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'patient_id',
        'number',
        'year',
        'progressive_number',
        'issued_at',
        'service',
        'quantity',
        'line_amount',
        'amount',
        'payment_method',
        'payment_date',
        'status',
        'description',
        'xml_downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'quantity' => 'decimal:2',
            'line_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'xml_downloaded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
