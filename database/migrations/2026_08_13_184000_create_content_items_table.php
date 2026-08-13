<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_items')) {
            return;
        }

        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('brand', 80);
            $table->string('content_pillar')->nullable();
            $table->string('audience')->nullable();
            $table->text('source_idea');
            $table->text('source_url')->nullable();
            $table->json('core_claims')->nullable();
            $table->string('source_hash', 64);
            $table->longText('master_draft')->nullable();
            $table->json('derivatives')->nullable();
            $table->json('claim_verification')->nullable();
            $table->string('status', 30)->default('idea');
            $table->text('review_notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'brand', 'source_hash'], 'content_owner_brand_source_unique');
            $table->index(['user_id', 'status', 'created_at'], 'content_owner_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_items');
    }
};
