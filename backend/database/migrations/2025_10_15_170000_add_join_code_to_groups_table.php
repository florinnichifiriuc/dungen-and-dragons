<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->string('join_code', 16)->after('slug')->unique();
        });

        DB::table('groups')->select('id')->orderBy('id')->chunkById(100, function ($groups): void {
            foreach ($groups as $group) {
                DB::table('groups')
                    ->where('id', $group->id)
                    ->update(['join_code' => Str::upper(Str::random(10))]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropColumn('join_code');
        });
    }
};
