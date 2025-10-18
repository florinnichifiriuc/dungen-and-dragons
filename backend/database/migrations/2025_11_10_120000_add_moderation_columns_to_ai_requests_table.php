<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_requests', function (Blueprint $table): void {
            $table->string('moderation_status', 20)->default('pending')->after('status');
            $table->text('moderation_notes')->nullable()->after('moderation_status');
            $table->foreignId('moderated_by')->nullable()->after('moderation_notes')->constrained('users')->nullOnDelete();
            $table->timestampTz('moderated_at')->nullable()->after('moderated_by');

            $table->index(['request_type', 'moderation_status']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_requests', function (Blueprint $table): void {
            $table->dropIndex('ai_requests_request_type_moderation_status_index');

            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn([
                'moderation_status',
                'moderation_notes',
                'moderated_at',
            ]);
        });
    }
};
