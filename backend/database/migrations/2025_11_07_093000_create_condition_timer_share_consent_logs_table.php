<?php

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
        Schema::create('condition_timer_share_consent_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Group::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 16);
            $table->string('visibility', 20)->default('counts');
            $table->string('source', 32)->default('facilitator');
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['group_id', 'user_id']);
            $table->index(['group_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_timer_share_consent_logs');
    }
};
