<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentSession extends Model
{
    protected $fillable = [
        'appointment_id',
        'invoice_id',
        'session_date',
        'title',
        'objective',
        'treatment',
        'pain_level',
        'outcome',
        'notes',
        'fee',
        'paid',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'fee' => 'decimal:2',
            'paid' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
