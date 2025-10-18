<?php

use App\Models\ConditionTimerSummaryShare;
use App\Models\User;
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
        Schema::create('condition_timer_summary_share_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(ConditionTimerSummaryShare::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('event_type', 32)->default('access');
            $table->timestampTz('occurred_at');
            $table->string('ip_hash', 128)->nullable();
            $table->string('user_agent_hash', 128)->nullable();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->boolean('quiet_hour_suppressed')->default(false);
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->index([
                'condition_timer_summary_share_id',
                'occurred_at',
            ], 'ctssa_share_occurred_at_index');
            $table->index([
                'condition_timer_summary_share_id',
                'event_type',
            ], 'ctssa_share_event_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_timer_summary_share_accesses');
    }
};
