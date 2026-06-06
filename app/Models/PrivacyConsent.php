<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyConsent extends Model
{
    protected $fillable = [
        'privacy_policy_accepted',
        'health_data_processing_accepted',
        'marketing_accepted',
        'signed_at',
        'signature_method',
        'document_version',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'privacy_policy_accepted' => 'boolean',
            'health_data_processing_accepted' => 'boolean',
            'marketing_accepted' => 'boolean',
            'signed_at' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
