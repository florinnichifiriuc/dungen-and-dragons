<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turn_configurations', function (Blueprint $table): void {
            $table->timestampTz('last_processed_at')->nullable()->after('next_turn_at');
        });
    }

    public function down(): void
    {
        Schema::table('turn_configurations', function (Blueprint $table): void {
            $table->dropColumn('last_processed_at');
        });
    }
};
