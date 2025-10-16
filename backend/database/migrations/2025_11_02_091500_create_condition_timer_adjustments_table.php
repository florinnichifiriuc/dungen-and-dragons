<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condition_timer_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_token_id')->constrained()->cascadeOnDelete();
            $table->string('condition_key');
            $table->integer('previous_rounds')->nullable();
            $table->integer('new_rounds')->nullable();
            $table->integer('delta')->nullable();
            $table->string('reason');
            $table->json('context')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['group_id', 'map_token_id', 'condition_key', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condition_timer_adjustments');
    }
};
