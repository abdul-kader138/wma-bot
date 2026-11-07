<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // See create_knowledge_base_tables.php's class-level note: this connection is a
    // separate Postgres instance, skipped entirely when PGVECTOR_HOST isn't configured.
    private const CONNECTION = 'pgvector';

    public function up(): void
    {
        if (! config('database.connections.'.self::CONNECTION.'.host')) {
            return;
        }

        Schema::connection(self::CONNECTION)->table('knowledge_documents', function (Blueprint $table) {
            // Bumped by KnowledgeDocument's saving() hook whenever content/title/service/
            // language actually change — a staff edit "counts" as a review. Staleness is
            // computed from COALESCE(reviewed_at, created_at) vs now()->subMonths(N).
            $table->timestamp('reviewed_at')->nullable();
        });

        Schema::connection(self::CONNECTION)->table('knowledge_chunks', function (Blueprint $table) {
            // sha256 of the chunk's content, used by IngestKnowledgeDocument to reuse an
            // existing embedding instead of re-calling Voyage for unchanged chunks.
            $table->string('content_hash', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! config('database.connections.'.self::CONNECTION.'.host')) {
            return;
        }

        Schema::connection(self::CONNECTION)->table('knowledge_documents', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
        });

        Schema::connection(self::CONNECTION)->table('knowledge_chunks', function (Blueprint $table) {
            $table->dropColumn('content_hash');
        });
    }
};
