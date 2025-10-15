<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_sessions', function (Blueprint $table): void {
            $table->string('recording_disk')->nullable()->after('recording_url');
            $table->string('recording_path')->nullable()->after('recording_disk');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_sessions', function (Blueprint $table): void {
            $table->dropColumn(['recording_path', 'recording_disk']);
        });
    }
};
