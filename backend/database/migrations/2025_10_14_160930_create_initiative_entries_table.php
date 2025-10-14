<?php

use App\Models\CampaignSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initiative_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(CampaignSession::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->integer('dexterity_mod')->default(0);
            $table->integer('initiative');
            $table->boolean('is_current')->default(false);
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();

            $table->index(['campaign_session_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initiative_entries');
    }
};
