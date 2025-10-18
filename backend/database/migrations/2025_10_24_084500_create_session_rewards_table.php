<?php

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Campaign::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(CampaignSession::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'recorded_by')->constrained('users')->cascadeOnDelete();
            $table->string('reward_type', 32);
            $table->string('title');
            $table->unsignedInteger('quantity')->nullable();
            $table->string('awarded_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['campaign_session_id', 'reward_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_rewards');
    }
};
