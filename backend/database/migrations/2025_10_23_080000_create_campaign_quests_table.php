<?php

use App\Models\Campaign;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_quests', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Campaign::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Region::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('details')->nullable();
            $table->string('status')->default('planned');
            $table->string('priority')->default('standard');
            $table->unsignedInteger('target_turn_number')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['campaign_id', 'status']);
            $table->index(['campaign_id', 'priority']);
            $table->index(['campaign_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_quests');
    }
};
