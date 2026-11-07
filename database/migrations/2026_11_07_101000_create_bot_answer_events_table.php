<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Logs every free-text answer the bot sends during IN_SERVICE (FAQ hit, knowledge-base
     * hit, or neither), so staff can see hit rates and which real customer questions went
     * unanswered, plus the 👍/👎 rating a customer taps afterwards. Drives the analytics
     * dashboard and the "weak answers" list.
     */
    public function up(): void
    {
        Schema::create('bot_answer_events', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('whatsapp_account_id')->index();
            // Plain column, not a FK: conversations can be bulk-reset/pruned independently
            // and this table is append-only history, not something that should cascade.
            $table->unsignedBigInteger('conversation_id')->index();
            $table->string('wa_phone')->index();
            $table->string('platform')->default('whatsapp');
            $table->string('service')->nullable();
            $table->string('language')->nullable();

            $table->text('question');
            $table->text('answer');

            // 'faq' (FaqMatcher hit, Claude never called), 'knowledge' (chunks found and
            // used), or 'none' (Claude answered with no reference material at all).
            $table->string('match_source')->index();
            // Plain column, not a FK: Faq lives fine on the default connection, but keeping
            // this table decoupled means a deleted FAQ never breaks historical analytics rows.
            $table->unsignedBigInteger('faq_id')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->float('top_distance')->nullable();

            // 1 = thumbs up, -1 = thumbs down, null = not rated.
            $table->tinyInteger('rating')->nullable();
            $table->timestamp('rated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_answer_events');
    }
};
