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
        Schema::table('patients', function (Blueprint $table) {
            $table->string('profession')->nullable()->after('email');
            $table->string('gender')->nullable()->after('birth_date');
            $table->string('birth_place')->nullable()->after('gender');
            $table->string('city')->nullable()->after('address');
            $table->string('province', 2)->nullable()->after('city');
            $table->string('postal_code', 10)->nullable()->after('province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'profession',
                'gender',
                'birth_place',
                'city',
                'province',
                'postal_code',
            ]);
        });
    }
};
