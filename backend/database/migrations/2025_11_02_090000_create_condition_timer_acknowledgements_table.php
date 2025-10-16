<?php

use App\Models\Group;
use App\Models\MapToken;
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
        Schema::create('condition_timer_acknowledgements', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Group::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(MapToken::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('condition_key', 64);
            $table->timestampTz('summary_generated_at');
            $table->timestampTz('acknowledged_at');
            $table->timestamps();

            $table->unique([
                'group_id',
                'map_token_id',
                'user_id',
                'condition_key',
            ], 'condition_acknowledgements_unique');

            $table->index([
                'group_id',
                'summary_generated_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_timer_acknowledgements');
    }
};
