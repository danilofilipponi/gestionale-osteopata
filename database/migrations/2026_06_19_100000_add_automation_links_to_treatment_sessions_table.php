<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->after('appointment_id')->constrained()->nullOnDelete();

            $table->unique('appointment_id', 'treatment_sessions_appointment_unique');
            $table->unique('invoice_id', 'treatment_sessions_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->dropUnique('treatment_sessions_appointment_unique');
            $table->dropUnique('treatment_sessions_invoice_unique');
            $table->dropConstrainedForeignId('appointment_id');
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
