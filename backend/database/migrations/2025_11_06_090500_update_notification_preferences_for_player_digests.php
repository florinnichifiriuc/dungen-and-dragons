<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('digest_channel_in_app')->default(true)->after('digest_delivery');
            $table->boolean('digest_channel_email')->default(true)->after('digest_channel_in_app');
            $table->boolean('digest_channel_push')->default(false)->after('digest_channel_email');
            $table->timestampTz('digest_last_sent_at')->nullable()->after('digest_channel_push');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropColumn([
                'digest_channel_in_app',
                'digest_channel_email',
                'digest_channel_push',
                'digest_last_sent_at',
            ]);
        });
    }
};
