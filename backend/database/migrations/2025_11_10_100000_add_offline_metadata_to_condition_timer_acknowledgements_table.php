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
        Schema::table('condition_timer_acknowledgements', function (Blueprint $table): void {
            $table->timestampTz('queued_at')->nullable()->after('acknowledged_at');
            $table->string('source', 32)->default('online')->after('queued_at');

            $table->index('queued_at');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condition_timer_acknowledgements', function (Blueprint $table): void {
            $table->dropIndex('condition_timer_acknowledgements_queued_at_index');
            $table->dropIndex('condition_timer_acknowledgements_source_index');

            $table->dropColumn(['queued_at', 'source']);
        });
    }
};
