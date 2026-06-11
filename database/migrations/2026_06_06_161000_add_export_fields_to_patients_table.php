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
            $table->string('pec')->nullable()->after('email');
            $table->string('country_id', 2)->default('IT')->after('profession');
            $table->string('customer_type')->default('Privato')->after('notes');
            $table->string('telematic_address')->default('0000000')->after('customer_type');
            $table->string('vat_number')->nullable()->after('telematic_address');
            $table->string('business_name')->nullable()->after('vat_number');
            $table->string('eori_code')->nullable()->after('business_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'pec',
                'country_id',
                'customer_type',
                'telematic_address',
                'vat_number',
                'business_name',
                'eori_code',
            ]);
        });
    }
};
