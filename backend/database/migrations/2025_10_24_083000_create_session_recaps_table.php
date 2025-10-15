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
        Schema::create('session_recaps', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Campaign::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(CampaignSession::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['campaign_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_recaps');
    }
};
