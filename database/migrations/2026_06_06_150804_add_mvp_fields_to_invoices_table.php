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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('service')->nullable()->after('issued_at');
            $table->string('payment_method')->nullable()->after('amount');
            $table->unsignedInteger('year')->nullable()->after('number');
            $table->unsignedInteger('progressive_number')->nullable()->after('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'service',
                'payment_method',
                'year',
                'progressive_number',
            ]);
        });
    }
};
