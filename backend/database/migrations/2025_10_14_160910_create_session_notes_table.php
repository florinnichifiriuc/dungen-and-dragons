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
        Schema::create('session_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Campaign::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(CampaignSession::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'author_id')->constrained('users')->cascadeOnDelete();
            $table->string('visibility')->default('players');
            $table->boolean('is_pinned')->default(false);
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campaign_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_notes');
    }
};
