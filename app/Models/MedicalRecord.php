<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecord extends Model
{
    protected $fillable = [
        'reason_for_visit',
        'symptoms_started_at',
        'pain_description',
        'irradiation',
        'exams',
        'previous_treatments',
        'traumas',
        'surgeries',
        'visceral_issues',
        'prosthesis_and_devices',
        'orthodontics',
        'family_history',
        'birth_history',
        'lifestyle',
        'sport',
        'physical_sphere',
        'psychological_sphere',
        'medications',
        'clinical_tests',
        'anamnesis',
        'diagnostic_notes',
        'treatment_plan',
        'contraindications',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
