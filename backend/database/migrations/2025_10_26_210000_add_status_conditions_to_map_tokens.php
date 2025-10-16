<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('map_tokens', function (Blueprint $table) {
            $table->json('status_conditions')->nullable()->after('status_effects');
        });
    }

    public function down(): void
    {
        Schema::table('map_tokens', function (Blueprint $table) {
            $table->dropColumn('status_conditions');
        });
    }
};
