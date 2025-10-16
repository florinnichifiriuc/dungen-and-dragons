<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_tokens', function (Blueprint $table) {
            $table->json('status_condition_durations')->nullable()->after('status_conditions');
        });
    }

    public function down(): void
    {
        Schema::table('map_tokens', function (Blueprint $table) {
            $table->dropColumn('status_condition_durations');
        });
    }
};
