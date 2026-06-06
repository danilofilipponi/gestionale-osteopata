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
        Schema::create('treatment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->string('title')->default('Seduta osteopatica');
            $table->text('objective')->nullable();
            $table->text('treatment')->nullable();
            $table->text('outcome')->nullable();
            $table->decimal('fee', 8, 2)->nullable();
            $table->boolean('paid')->default(false);
            $table->timestamps();

            $table->index(['patient_id', 'session_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_sessions');
    }
};
