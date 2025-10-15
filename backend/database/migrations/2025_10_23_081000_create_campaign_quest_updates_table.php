<?php

use App\Models\CampaignQuest;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_quest_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(CampaignQuest::class, 'quest_id')->constrained('campaign_quests')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('summary');
            $table->longText('details')->nullable();
            $table->timestampTz('recorded_at')->nullable();
            $table->timestampsTz();

            $table->index(['quest_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_quest_updates');
    }
};
