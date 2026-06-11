<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyConsent extends Model
{
    protected $fillable = [
        'privacy_policy_accepted',
        'health_data_processing_accepted',
        'osteopathic_treatment_accepted',
        'doctor_data_sharing_accepted',
        'family_data_sharing_accepted',
        'whatsapp_reminders_accepted',
        'email_reminders_accepted',
        'sms_reminders_accepted',
        'marketing_accepted',
        'signed_at',
        'signature_method',
        'signature_data',
        'document_version',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'privacy_policy_accepted' => 'boolean',
            'health_data_processing_accepted' => 'boolean',
            'osteopathic_treatment_accepted' => 'boolean',
            'doctor_data_sharing_accepted' => 'boolean',
            'family_data_sharing_accepted' => 'boolean',
            'whatsapp_reminders_accepted' => 'boolean',
            'email_reminders_accepted' => 'boolean',
            'sms_reminders_accepted' => 'boolean',
            'marketing_accepted' => 'boolean',
            'signed_at' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
