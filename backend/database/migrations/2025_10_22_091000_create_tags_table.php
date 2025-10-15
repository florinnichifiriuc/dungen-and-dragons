<?php

use App\Models\Campaign;
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
        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Campaign::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('slug');
            $table->string('color', 16)->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
