<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->timestampTz('window_started_at');
            $table->timestampTz('processed_at');
            $table->foreignId('processed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('used_ai_fallback')->default(false);
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->unique(['region_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turns');
    }
};
