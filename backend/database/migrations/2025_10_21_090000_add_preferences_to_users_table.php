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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 8)->default('en')->after('remember_token');
            $table->string('timezone')->default('UTC')->after('locale');
            $table->string('theme')->default('system')->after('timezone');
            $table->boolean('high_contrast')->default(false)->after('theme');
            $table->unsignedTinyInteger('font_scale')->default(100)->after('high_contrast');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'locale',
                'timezone',
                'theme',
                'high_contrast',
                'font_scale',
            ]);
        });
    }
};
