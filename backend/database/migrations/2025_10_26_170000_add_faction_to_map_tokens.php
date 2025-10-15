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
            $table->string('faction', 20)->default('neutral')->after('size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_tokens', function (Blueprint $table): void {
            $table->dropColumn('faction');
        });
    }
};
