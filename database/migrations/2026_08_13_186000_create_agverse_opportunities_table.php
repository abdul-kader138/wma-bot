<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agverse_opportunities')) {
            return;
        }

        Schema::create('agverse_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('organization')->nullable();
            $table->text('summary');
            $table->decimal('expected_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('AED');
            $table->unsignedTinyInteger('value_score')->default(1);
            $table->unsignedTinyInteger('strategic_fit_score')->default(1);
            $table->unsignedTinyInteger('urgency_score')->default(1);
            $table->unsignedTinyInteger('evidence_score')->default(1);
            $table->unsignedTinyInteger('effort_score')->default(1);
            $table->unsignedTinyInteger('risk_score')->default(1);
            $table->integer('priority_score')->default(0);
            $table->json('verified_facts')->nullable();
            $table->json('hypotheses')->nullable();
            $table->json('evidence_links')->nullable();
            $table->text('next_step')->nullable();
            $table->string('next_step_owner')->nullable();
            $table->timestamp('next_step_at')->nullable();
            $table->boolean('approval_required')->default(false);
            $table->string('stage', 30)->default('research');
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status', 'priority_score'], 'agv_owner_status_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agverse_opportunities');
    }
};
