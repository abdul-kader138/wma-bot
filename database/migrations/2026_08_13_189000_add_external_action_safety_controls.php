<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assistant_profiles', 'external_actions_enabled')) {
            Schema::table('assistant_profiles', fn (Blueprint $table) => $table->boolean('external_actions_enabled')->default(false)->after('is_active'));
        }
        if (! Schema::hasTable('action_reconciliations')) {
            Schema::create('action_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assistant_action_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('provider', 30);
                $table->string('status', 30)->default('pending');
                $table->text('reason');
                $table->json('provider_evidence')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status', 'created_at'], 'reconcile_owner_status_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('action_reconciliations');
        if (Schema::hasColumn('assistant_profiles', 'external_actions_enabled')) {
            Schema::table('assistant_profiles', fn (Blueprint $table) => $table->dropColumn('external_actions_enabled'));
        }
    }
};
