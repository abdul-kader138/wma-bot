<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('relationship_recommendations')) {
            if (! Schema::hasIndex('relationship_recommendations', 'rel_rec_owner_status_date_idx')) {
                Schema::table('relationship_recommendations', function (Blueprint $table) {
                    $table->index(['user_id', 'status', 'recommendation_date'], 'rel_rec_owner_status_date_idx');
                });
            }

            return;
        }

        Schema::create('relationship_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maria_contact_id')->constrained('maria_contacts')->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained()->nullOnDelete();
            $table->date('recommendation_date');
            $table->unsignedSmallInteger('score')->default(0);
            $table->text('relevance');
            $table->text('warm_path')->nullable();
            $table->text('suggested_comment')->nullable();
            $table->text('connection_note')->nullable();
            $table->text('follow_up')->nullable();
            $table->string('recommended_stage', 30);
            $table->timestamp('next_action_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'recommendation_date', 'maria_contact_id'], 'relationship_daily_contact_unique');
            $table->index(['user_id', 'status', 'recommendation_date'], 'rel_rec_owner_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_recommendations');
    }
};
