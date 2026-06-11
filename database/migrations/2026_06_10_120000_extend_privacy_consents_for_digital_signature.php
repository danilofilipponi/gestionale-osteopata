<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('privacy_consents', function (Blueprint $table) {
            $table->boolean('osteopathic_treatment_accepted')->default(false)->after('health_data_processing_accepted');
            $table->boolean('doctor_data_sharing_accepted')->default(false)->after('osteopathic_treatment_accepted');
            $table->boolean('family_data_sharing_accepted')->default(false)->after('doctor_data_sharing_accepted');
            $table->boolean('whatsapp_reminders_accepted')->default(false)->after('family_data_sharing_accepted');
            $table->boolean('email_reminders_accepted')->default(false)->after('whatsapp_reminders_accepted');
            $table->boolean('sms_reminders_accepted')->default(false)->after('email_reminders_accepted');
            $table->longText('signature_data')->nullable()->after('signature_method');
        });
    }

    public function down(): void
    {
        Schema::table('privacy_consents', function (Blueprint $table) {
            $table->dropColumn([
                'osteopathic_treatment_accepted',
                'doctor_data_sharing_accepted',
                'family_data_sharing_accepted',
                'whatsapp_reminders_accepted',
                'email_reminders_accepted',
                'sms_reminders_accepted',
                'signature_data',
            ]);
        });
    }
};
