<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('connector_accounts', 'email')) {
            Schema::table('connector_accounts', function (Blueprint $table) {
                $table->string('email')->nullable()->after('provider_account_id');
            });
        }

        if (! Schema::hasColumn('maria_contacts', 'email')) {
            Schema::table('maria_contacts', function (Blueprint $table) {
                $table->string('email')->nullable()->after('organization');
                $table->index(['user_id', 'email']);
            });
        }

        if (! Schema::hasColumn('maria_tasks', 'deduplication_key')) {
            Schema::table('maria_tasks', function (Blueprint $table) {
                $table->string('deduplication_key', 64)->nullable()->after('priority_reason');
                $table->foreignId('possible_duplicate_of_id')->nullable()->after('deduplication_key')
                    ->constrained('maria_tasks')->nullOnDelete();
                $table->index(['user_id', 'deduplication_key']);
            });
        }

        if (! Schema::hasTable('communications')) {
            Schema::create('communications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contact_id')->nullable()->constrained('maria_contacts')->nullOnDelete();
                $table->foreignId('connector_account_id')->nullable()->constrained()->nullOnDelete();
                $table->string('thread_source_id');
                $table->string('message_source_id')->nullable();
                $table->string('channel', 30)->default('email');
                $table->string('direction', 20)->default('inbound');
                $table->string('classification', 30)->nullable();
                $table->text('summary')->nullable();
                $table->json('commitments')->nullable();
                $table->longText('draft_response')->nullable();
                $table->string('sensitivity', 20)->default('internal');
                $table->timestamp('follow_up_at')->nullable();
                $table->string('source_url')->nullable();
                $table->json('source_metadata')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'channel', 'message_source_id'], 'communication_source_unique');
                $table->index(['user_id', 'classification', 'follow_up_at']);
            });
        }

        if (! Schema::hasTable('meetings')) {
            Schema::create('meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('connector_account_id')->nullable()->constrained()->nullOnDelete();
                $table->string('calendar_event_id');
                $table->string('title');
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->json('attendees')->nullable();
                $table->string('domain', 10)->nullable();
                $table->string('tier', 1)->nullable();
                $table->text('objective')->nullable();
                $table->string('preparation_status', 30)->default('pending');
                $table->json('brief')->nullable();
                $table->text('notes_source')->nullable();
                $table->json('decisions')->nullable();
                $table->json('action_items')->nullable();
                $table->longText('thank_you_draft')->nullable();
                $table->timestamp('follow_up_at')->nullable();
                $table->string('confidentiality', 20)->default('internal');
                $table->timestamps();
                $table->unique(['user_id', 'calendar_event_id']);
                $table->index(['user_id', 'starts_at', 'preparation_status']);
            });
        }

        if (! Schema::hasTable('assistant_briefs')) {
            Schema::create('assistant_briefs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_run_id')->constrained()->cascadeOnDelete();
                $table->string('type', 40);
                $table->date('brief_date');
                $table->json('content');
                $table->timestamps();
                $table->unique(['user_id', 'type', 'brief_date']);
            });
        }

        if (! Schema::hasTable('assistant_alerts')) {
            Schema::create('assistant_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type', 40);
                $table->string('severity', 20)->default('normal');
                $table->string('subject_type', 80);
                $table->unsignedBigInteger('subject_id');
                $table->string('state_hash', 64);
                $table->string('status', 20)->default('active');
                $table->text('message');
                $table->timestamp('first_seen_at');
                $table->timestamp('last_seen_at');
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'type', 'subject_type', 'subject_id', 'state_hash'], 'assistant_alert_state_unique');
                $table->index(['user_id', 'status', 'severity']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_briefs');
        Schema::dropIfExists('assistant_alerts');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('communications');

        if (Schema::hasColumn('maria_tasks', 'deduplication_key')) {
            Schema::table('maria_tasks', function (Blueprint $table) {
                $table->dropConstrainedForeignId('possible_duplicate_of_id');
                $table->dropIndex(['user_id', 'deduplication_key']);
                $table->dropColumn('deduplication_key');
            });
        }

        if (Schema::hasColumn('maria_contacts', 'email')) {
            Schema::table('maria_contacts', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'email']);
                $table->dropColumn('email');
            });
        }

        // connector_accounts.email belongs to the foundation schema. It is only
        // added here as a compatibility repair for databases that ran an early
        // development version, so it must not be removed during rollback.
    }
};
