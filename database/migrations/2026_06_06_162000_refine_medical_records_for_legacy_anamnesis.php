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
            $table->text('symptoms_started_at')->nullable()->change();
            $table->text('orthodontics')->nullable()->after('prosthesis_and_devices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->date('symptoms_started_at')->nullable()->change();
            $table->dropColumn('orthodontics');
        });
    }
};
