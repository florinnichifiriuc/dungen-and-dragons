<?php

use App\Models\Map;
use App\Models\TileTemplate;
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
        Schema::create('map_tiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Map::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(TileTemplate::class)->constrained()->cascadeOnDelete();
            $table->integer('q');
            $table->integer('r');
            $table->enum('orientation', ['pointy', 'flat']);
            $table->smallInteger('elevation')->default(0);
            $table->json('variant')->nullable();
            $table->boolean('locked')->default(false);
            $table->timestamps();

            $table->unique(['map_id', 'q', 'r']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_tiles');
    }
};
