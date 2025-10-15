<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table): void {
            $table->boolean('ai_controlled')->default(false)->after('dungeon_master_id');
            $table->longText('ai_delegate_summary')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table): void {
            $table->dropColumn(['ai_controlled', 'ai_delegate_summary']);
        });
    }
};
