<?php

use App\Models\Group;
use App\Models\User;
use App\Models\World;
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
        Schema::create('tile_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Group::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(World::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('key', 64)->nullable();
            $table->string('name', 120);
            $table->string('terrain_type', 64);
            $table->unsignedTinyInteger('movement_cost')->default(1);
            $table->unsignedTinyInteger('defense_bonus')->default(0);
            $table->string('image_path')->nullable();
            $table->json('edge_profile')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tile_templates');
    }
};
