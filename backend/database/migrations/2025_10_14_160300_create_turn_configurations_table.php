<?php

use App\Models\Region;
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
        Schema::create('turn_configurations', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Region::class)->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('turn_duration_hours');
            $table->timestampTz('next_turn_at')->nullable();
            $table->timestamps();

            $table->unique('region_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turn_configurations');
    }
};
