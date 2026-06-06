<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentSession extends Model
{
    protected $fillable = [
        'session_date',
        'title',
        'objective',
        'treatment',
        'outcome',
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
}
