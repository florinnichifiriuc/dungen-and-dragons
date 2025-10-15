<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('request_type', 50);
            $table->string('context_type');
            $table->unsignedBigInteger('context_id');
            $table->json('meta')->nullable();
            $table->longText('prompt');
            $table->longText('response_text')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('provider', 50)->default('ollama');
            $table->string('model', 100)->default('gemma3');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->string('error_message')->nullable();
            $table->timestampsTz();

            $table->index(['context_type', 'context_id']);
            $table->index(['request_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
