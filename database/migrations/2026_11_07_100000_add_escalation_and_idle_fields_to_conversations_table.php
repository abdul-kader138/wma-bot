<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Escalation ("talk to a human"): step becomes 'HUMAN' while these are set.
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalation_status')->nullable()->index();

            // Idle follow-up: last_customer_message_at is bumped on every genuine
            // inbound message (never on the bot's own writes); idle_nudged_at marks
            // when a nudge last went out, so at most one nudge fires per quiet spell.
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('idle_nudged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['escalated_at', 'escalation_status', 'last_customer_message_at', 'idle_nudged_at']);
        });
    }
};
