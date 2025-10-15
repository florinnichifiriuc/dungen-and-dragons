<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->uuid('campaign_session_id');
            $table->uuid('recorded_by');
            $table->string('reward_type', 32);
            $table->string('title');
            $table->unsignedInteger('quantity')->nullable();
            $table->string('awarded_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->foreign('campaign_session_id')->references('id')->on('campaign_sessions')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['campaign_session_id', 'reward_type']);
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_rewards');
    }
};
