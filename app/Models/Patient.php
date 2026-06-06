<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'birth_place',
        'fiscal_code',
        'phone',
        'email',
        'profession',
        'address',
        'city',
        'province',
        'postal_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function treatmentSessions(): HasMany
    {
        return $this->hasMany(TreatmentSession::class)->latest('session_date');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('issued_at');
    }

    public function privacyConsent(): HasOne
    {
        return $this->hasOne(PrivacyConsent::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->latest('starts_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest('scheduled_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }
}
