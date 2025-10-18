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
            $table->string('preset_key', 64)->nullable()->after('visibility_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condition_timer_summary_shares', function (Blueprint $table): void {
            $table->dropColumn('preset_key');
        });
    }
};
