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
        Schema::create('condition_transparency_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Group::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('format', 12);
            $table->string('visibility_mode', 20)->default('counts');
            $table->json('filters')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('file_path')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('retry_attempts')->default(0);
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->index(['group_id', 'status']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_transparency_exports');
    }
};
