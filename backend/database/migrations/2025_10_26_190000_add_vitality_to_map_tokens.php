<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('map_tokens', function (Blueprint $table) {
            $table->integer('hit_points')->nullable()->after('status_effects');
            $table->integer('temporary_hit_points')->nullable()->after('hit_points');
            $table->integer('max_hit_points')->nullable()->after('temporary_hit_points');
        });
    }

    public function down(): void
    {
        Schema::table('map_tokens', function (Blueprint $table) {
            $table->dropColumn(['hit_points', 'temporary_hit_points', 'max_hit_points']);
        });
    }
};
