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
        Schema::create('dice_rolls', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Campaign::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(CampaignSession::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'roller_id')->constrained('users')->cascadeOnDelete();
            $table->string('expression');
            $table->json('result_breakdown')->nullable();
            $table->integer('result_total');
            $table->timestamps();

            $table->index(['campaign_id', 'campaign_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dice_rolls');
    }
};
