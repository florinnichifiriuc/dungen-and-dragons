<?php

use App\Models\Map;
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
        Schema::create('map_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Map::class)->constrained()->cascadeOnDelete();
            $table->nullableMorphs('entity');
            $table->string('name');
            $table->integer('x');
            $table->integer('y');
            $table->string('color', 32)->nullable();
            $table->string('size', 24)->default('medium');
            $table->boolean('hidden')->default(false);
            $table->text('gm_note')->nullable();
            $table->timestamps();

            $table->index(['map_id', 'hidden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_tokens');
    }
};
