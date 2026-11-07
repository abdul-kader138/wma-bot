<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Set when the customer sends a STOP/unsubscribe keyword; cleared the moment
            // they message again (that's treated as renewed consent). Only gates proactive
            // sends (SendIdleFollowUps) — never blocks the bot's normal reactive replies.
            $table->timestamp('opted_out_at')->nullable();

            // Manual claim for the escalation queue: null = unclaimed/waiting, set when a
            // staff member claims it from ConversationResource. Same connection as `users`,
            // so (unlike the pgvector tables) a real FK is fine here.
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropColumn('opted_out_at');
        });
    }
};
