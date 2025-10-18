<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->string('submitted_email')->nullable();
            $table->string('submitted_name')->nullable();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('context_type');
            $table->string('context_identifier')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('normal');
            $table->string('summary');
            $table->text('description');
            $table->json('environment')->nullable();
            $table->json('ai_context')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
