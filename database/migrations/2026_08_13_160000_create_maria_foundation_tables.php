<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('language', 10)->default('en');
            $table->time('working_hours_start')->nullable();
            $table->time('working_hours_end')->nullable();
            $table->time('morning_brief_at')->nullable();
            $table->time('evening_review_at')->nullable();
            $table->json('enabled_workflows')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->text('voice_preferences')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assistant_channel_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('external_identifier');
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'whatsapp_account_id', 'external_identifier'], 'assistant_channel_identity_unique');
        });

        Schema::create('connector_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('provider_account_id')->nullable();
            $table->string('email')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'provider_account_id'], 'connector_owner_provider_unique');
        });

        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_channel_identity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('active');
            $table->json('history')->nullable();
            $table->string('last_prompt_version')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['assistant_profile_id', 'status', 'last_message_at'], 'assistant_conversation_profile_status');
        });

        Schema::create('maria_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain', 10)->index();
            $table->string('name');
            $table->text('desired_outcome');
            $table->string('stage', 30)->default('idea');
            $table->string('priority', 20)->default('normal');
            $table->string('owner_name');
            $table->text('next_action');
            $table->timestamp('next_action_at');
            $table->timestamp('deadline_at')->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->text('blocker')->nullable();
            $table->string('confidentiality', 20)->default('internal');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'next_action_at']);
        });

        Schema::create('maria_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('role')->nullable();
            $table->string('organization')->nullable();
            $table->json('categories')->nullable();
            $table->string('tier', 1)->nullable();
            $table->json('domain_relevance')->nullable();
            $table->text('why_matters')->nullable();
            $table->text('verification_source')->nullable();
            $table->text('warm_path')->nullable();
            $table->string('stage', 30)->default('research');
            $table->timestamp('last_interaction_at')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->text('consent_preferences')->nullable();
            $table->text('private_contact_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stage', 'follow_up_at']);
        });

        Schema::create('maria_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('maria_projects')->nullOnDelete();
            $table->foreignId('waiting_on_contact_id')->nullable()->constrained('maria_contacts')->nullOnDelete();
            $table->text('task');
            $table->string('owner_name');
            $table->string('source', 30)->default('user');
            $table->string('source_reference')->nullable();
            $table->unsignedTinyInteger('mission_score')->default(1);
            $table->unsignedTinyInteger('relationship_score')->default(1);
            $table->unsignedTinyInteger('urgency_score')->default(1);
            $table->unsignedTinyInteger('strategic_score')->default(1);
            $table->unsignedTinyInteger('effort_score')->default(1);
            $table->unsignedTinyInteger('risk_score')->default(1);
            $table->integer('priority_score')->default(2);
            $table->text('priority_reason')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamp('follow_up_at')->nullable();
            $table->boolean('approval_required')->default(false);
            $table->string('evidence_url')->nullable();
            $table->json('recurrence')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'due_at']);
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('claim_text');
            $table->string('subject');
            $table->string('category', 50);
            $table->text('source_url')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('recheck_at')->nullable();
            $table->json('permitted_brands')->nullable();
            $table->string('status', 30)->default('unverified');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'recheck_at']);
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('workflow_type', 60)->index();
            $table->string('status', 30)->default('pending');
            $table->json('input_references')->nullable();
            $table->json('source_gaps')->nullable();
            $table->json('structured_output')->nullable();
            $table->string('prompt_version')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('estimated_cost', 12, 6)->default(0);
            $table->unsignedInteger('estimated_manual_minutes')->default(0);
            $table->unsignedInteger('human_minutes')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('prompt_type', 60);
            $table->string('version', 30);
            $table->longText('content');
            $table->json('output_schema')->nullable();
            $table->string('content_hash', 64);
            $table->boolean('is_active')->default(false);
            $table->text('change_notes')->nullable();
            $table->timestamps();

            $table->unique(['prompt_type', 'version']);
            $table->index(['prompt_type', 'is_active']);
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->string('action_type', 40);
            $table->text('proposed_action');
            $table->json('proposed_content');
            $table->longText('preview')->nullable();
            $table->string('recipient_channel')->nullable();
            $table->json('attachments')->nullable();
            $table->string('risk_level', 20)->default('low');
            $table->string('decision', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('expires_at');
            $table->string('content_hash', 64);
            $table->text('audit_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'decision', 'expires_at']);
        });

        Schema::create('assistant_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->foreignId('approval_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool_name', 80);
            $table->json('validated_input');
            $table->string('content_hash', 64);
            $table->string('idempotency_key', 64)->unique();
            $table->string('status', 30)->default('not_executed');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_confirmation_id')->nullable();
            $table->json('sanitized_result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::table('maria_tasks', function (Blueprint $table) {
            $table->foreignId('approval_id')->nullable()->after('approval_required')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maria_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_id');
        });

        Schema::dropIfExists('assistant_actions');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('prompt_versions');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('maria_tasks');
        Schema::dropIfExists('maria_contacts');
        Schema::dropIfExists('maria_projects');
        Schema::dropIfExists('assistant_conversations');
        Schema::dropIfExists('connector_accounts');
        Schema::dropIfExists('assistant_channel_identities');
        Schema::dropIfExists('assistant_profiles');
    }
};
