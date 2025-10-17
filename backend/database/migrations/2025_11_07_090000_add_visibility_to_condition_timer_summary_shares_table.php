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
        Schema::table('condition_timer_summary_shares', function (Blueprint $table): void {
            $table->string('visibility_mode', 20)->default('counts')->after('expires_at');
            $table->json('consent_snapshot')->nullable()->after('visibility_mode');
            $table->unsignedInteger('access_count')->default(0)->after('consent_snapshot');
            $table->timestampTz('last_accessed_at')->nullable()->after('access_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condition_timer_summary_shares', function (Blueprint $table): void {
            $table->dropColumn([
                'visibility_mode',
                'consent_snapshot',
                'access_count',
                'last_accessed_at',
            ]);
        });
    }
};
