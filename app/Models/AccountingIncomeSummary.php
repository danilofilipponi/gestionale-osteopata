<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingIncomeSummary extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'invoiced_amount',
        'gross_income_amount',
    ];

    protected function casts(): array
    {
        return [
            'invoiced_amount' => 'decimal:2',
            'gross_income_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
