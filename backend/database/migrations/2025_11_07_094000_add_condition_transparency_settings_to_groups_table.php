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
        Schema::table('groups', function (Blueprint $table): void {
            $table->boolean('mentor_briefings_enabled')->default(true)->after('telemetry_opt_out');
            $table->timestampTz('mentor_briefings_last_generated_at')->nullable()->after('mentor_briefings_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropColumn([
                'mentor_briefings_enabled',
                'mentor_briefings_last_generated_at',
            ]);
        });
    }
};
