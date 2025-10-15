<?php

use App\Models\CampaignSession;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(CampaignSession::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->enum('status', ['yes', 'maybe', 'no']);
            $table->text('note')->nullable();
            $table->timestampTz('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendances');
    }
};
