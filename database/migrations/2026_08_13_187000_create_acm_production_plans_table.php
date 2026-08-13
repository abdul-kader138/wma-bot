<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assistant_profiles', 'weekly_production_day')) {
            Schema::table('assistant_profiles', function (Blueprint $table) {
                $table->unsignedTinyInteger('weekly_production_day')->nullable()->after('evening_review_at');
                $table->time('weekly_production_at')->nullable()->after('weekly_production_day');
            });
        }
        if (! Schema::hasTable('acm_production_plans')) {
            Schema::create('acm_production_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_run_id')->nullable()->constrained()->nullOnDelete();
                $table->date('week_start');
                $table->string('theme');
                $table->text('source_notes')->nullable();
                $table->json('core_claims')->nullable();
                $table->string('owner_name');
                $table->timestamp('approval_deadline');
                $table->json('production_package')->nullable();
                $table->json('claim_verification')->nullable();
                $table->string('status', 30)->default('planned');
                $table->timestamp('generated_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'week_start'], 'acm_owner_week_unique');
                $table->index(['user_id', 'status', 'approval_deadline'], 'acm_owner_status_deadline_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acm_production_plans');
        if (Schema::hasColumn('assistant_profiles', 'weekly_production_day')) {
            Schema::table('assistant_profiles', function (Blueprint $table) {
                $table->dropColumn(['weekly_production_day', 'weekly_production_at']);
            });
        }
    }
};
