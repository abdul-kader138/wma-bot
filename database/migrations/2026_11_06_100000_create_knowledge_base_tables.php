<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Deliberately NOT the 'default' connection — see config/database.php's
    // 'pgvector' block. This keeps the main app on its real default (MySQL in
    // production) while this one small, isolated Postgres instance does only
    // vector search. Because of that, nothing here may have a real foreign key
    // or Eloquent relationship to a table on another connection (e.g.
    // whatsapp_accounts) — a single SQL query can't join across two different
    // database servers. Account/service scoping is always a plain column
    // compared by value, never a relationship.
    private const CONNECTION = 'pgvector';

    public function up(): void
    {
        // No pgvector connection configured (PGVECTOR_HOST empty) — skip entirely
        // rather than attempt a connection, so `migrate` still runs cleanly
        // everywhere the knowledge-base feature isn't set up yet.
        if (! config('database.connections.'.self::CONNECTION.'.host')) {
            return;
        }

        DB::connection(self::CONNECTION)->statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::connection(self::CONNECTION)->create('knowledge_documents', function (Blueprint $t) {
            $t->id();

            // Array of whatsapp_accounts.id (MySQL, a different connection) this
            // entry applies to; empty/null means "every account" — same semantics
            // as Faq's null service meaning "every service". Plain value, not a
            // relationship, for the cross-connection reason above.
            $t->json('account_ids')->nullable();
            $t->string('service')->nullable()->index();
            $t->string('title');
            $t->string('language', 5)->default('en');
            $t->text('content')->nullable();

            // Set when staff upload a PDF/DOCX/TXT instead of pasting text directly —
            // ExtractKnowledgeDocumentText reads this, fills `content`, then hands off
            // to IngestKnowledgeDocument. Null when content was typed/pasted directly.
            $t->string('source_disk')->nullable();
            $t->string('source_path')->nullable();

            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::connection(self::CONNECTION)->create('knowledge_chunks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('knowledge_document_id')->constrained()->cascadeOnDelete();

            // Denormalized from the document so KnowledgeRetriever can filter/scope
            // in the same query as the vector search, without a join on every lookup.
            // Plain nullable column, not a FK — see the class-level note above.
            $t->unsignedBigInteger('whatsapp_account_id')->nullable()->index();
            $t->string('service')->nullable()->index();
            $t->string('language', 5)->default('en');

            $t->unsignedInteger('chunk_index')->default(0);
            $t->text('content');
            $t->vector('embedding', (int) config('services.embeddings.voyage.dimensions', 512));

            $t->timestamps();
        });

        // HNSW index for cosine-distance search.
        DB::connection(self::CONNECTION)->statement(
            'CREATE INDEX knowledge_chunks_embedding_hnsw ON knowledge_chunks USING hnsw (embedding vector_cosine_ops)'
        );
    }

    public function down(): void
    {
        if (! config('database.connections.'.self::CONNECTION.'.host')) {
            return;
        }

        Schema::connection(self::CONNECTION)->dropIfExists('knowledge_chunks');
        Schema::connection(self::CONNECTION)->dropIfExists('knowledge_documents');
    }
};
