<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condition_timer_summary_share_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('condition_timer_summary_share_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestampTz('accessed_at')->useCurrent();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestampsTz();

            $table->index(['condition_timer_summary_share_id', 'accessed_at'], 'share_access_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condition_timer_summary_share_accesses');
    }
};
