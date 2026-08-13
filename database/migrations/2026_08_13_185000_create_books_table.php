<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('books')) {
            return;
        }

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('exact_title');
            $table->string('subtitle')->nullable();
            $table->text('credits')->nullable();
            $table->string('edition')->nullable();
            $table->string('stage', 40)->default('idea');
            $table->text('manuscript_url')->nullable();
            $table->string('current_milestone')->nullable();
            $table->string('milestone_owner')->nullable();
            $table->timestamp('milestone_due_at')->nullable();
            $table->text('blocker')->nullable();
            $table->json('contributors')->nullable();
            $table->date('publication_target')->nullable();
            $table->string('marketing_status', 40)->nullable();
            $table->text('next_action')->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'exact_title', 'edition'], 'book_owner_title_edition_unique');
            $table->index(['user_id', 'status', 'milestone_due_at'], 'book_owner_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
