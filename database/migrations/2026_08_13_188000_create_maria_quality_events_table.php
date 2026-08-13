<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('maria_quality_events')) {
            Schema::create('maria_quality_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_run_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type', 30);
                $table->string('category', 60);
                $table->string('severity', 20)->default('low');
                $table->text('description');
                $table->text('expected_result')->nullable();
                $table->text('actual_result')->nullable();
                $table->text('resolution')->nullable();
                $table->string('status', 20)->default('open');
                $table->timestamp('occurred_at');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'event_type', 'occurred_at'], 'quality_owner_type_date_idx');
            });
        }
        if (! Schema::hasColumn('workflow_runs', 'time_saving_verified_at')) {
            Schema::table('workflow_runs', function (Blueprint $table) {
                $table->unsignedInteger('verified_time_saved_minutes')->default(0)->after('human_minutes');
                $table->timestamp('time_saving_verified_at')->nullable()->after('verified_time_saved_minutes');
                $table->foreignId('time_saving_verified_by')->nullable()->after('time_saving_verified_at')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workflow_runs', 'time_saving_verified_by')) {
            Schema::table('workflow_runs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('time_saving_verified_by');
                $table->dropColumn(['verified_time_saved_minutes', 'time_saving_verified_at']);
            });
        }
        Schema::dropIfExists('maria_quality_events');
    }
};
