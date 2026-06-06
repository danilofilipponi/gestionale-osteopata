<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->date('symptoms_started_at')->nullable()->after('reason_for_visit');
            $table->text('pain_description')->nullable()->after('symptoms_started_at');
            $table->text('irradiation')->nullable()->after('pain_description');
            $table->text('exams')->nullable()->after('irradiation');
            $table->text('previous_treatments')->nullable()->after('exams');
            $table->text('traumas')->nullable()->after('previous_treatments');
            $table->text('surgeries')->nullable()->after('traumas');
            $table->text('visceral_issues')->nullable()->after('surgeries');
            $table->text('prosthesis_and_devices')->nullable()->after('visceral_issues');
            $table->text('family_history')->nullable()->after('prosthesis_and_devices');
            $table->text('birth_history')->nullable()->after('family_history');
            $table->text('lifestyle')->nullable()->after('birth_history');
            $table->text('sport')->nullable()->after('lifestyle');
            $table->text('physical_sphere')->nullable()->after('sport');
            $table->text('psychological_sphere')->nullable()->after('physical_sphere');
            $table->text('medications')->nullable()->after('psychological_sphere');
            $table->text('clinical_tests')->nullable()->after('medications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn([
                'symptoms_started_at',
                'pain_description',
                'irradiation',
                'exams',
                'previous_treatments',
                'traumas',
                'surgeries',
                'visceral_issues',
                'prosthesis_and_devices',
                'family_history',
                'birth_history',
                'lifestyle',
                'sport',
                'physical_sphere',
                'psychological_sphere',
                'medications',
                'clinical_tests',
            ]);
        });
    }
};
