<?php

use App\Models\Campaign;
use App\Models\Group;
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
        Schema::create('campaign_entities', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Campaign::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Group::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 32);
            $table->string('name');
            $table->string('slug');
            $table->string('alias')->nullable();
            $table->string('pronunciation')->nullable();
            $table->string('visibility', 16)->default('players');
            $table->boolean('ai_controlled')->default(false);
            $table->unsignedTinyInteger('initiative_default')->nullable();
            $table->json('stats')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['campaign_id', 'slug']);
            $table->index(['campaign_id', 'entity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_entities');
    }
};
