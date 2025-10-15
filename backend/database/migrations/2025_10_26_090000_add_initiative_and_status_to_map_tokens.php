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
        Schema::table('map_tokens', function (Blueprint $table): void {
            $table->smallInteger('initiative')->nullable()->after('size');
            $table->string('status_effects', 255)->nullable()->after('initiative');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_tokens', function (Blueprint $table): void {
            $table->dropColumn(['initiative', 'status_effects']);
        });
    }
};
