<?php

use App\Models\Group;
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
        Schema::create('condition_transparency_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Group::class)->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('secret', 64);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('call_count')->default(0);
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestampTz('last_triggered_at')->nullable();
            $table->timestampTz('last_failed_at')->nullable();
            $table->timestampsTz();

            $table->index(['group_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_transparency_webhooks');
    }
};
