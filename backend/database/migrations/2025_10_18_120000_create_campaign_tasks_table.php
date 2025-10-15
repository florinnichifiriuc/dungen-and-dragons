<?php

use App\Models\Campaign;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Campaign::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by_id')->constrained('users');
            $table->foreignIdFor(User::class, 'assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(Group::class, 'assigned_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('status', 32)->index();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('due_turn_number')->nullable()->index();
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_tasks');
    }
};
