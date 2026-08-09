<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backs both the per-phone daily cap and the global daily circuit breaker in
        // HandleIncomingMessage, plus the admin usage dashboard. Deliberately a plain
        // DB table rather than cache counters: the production cache store is Redis,
        // and unlike the 'database' cache driver, Redis has no simple way to enumerate
        // "which phone numbers have a counter today" for the dashboard — a real table
        // is driver-agnostic and gives the dashboard and the enforcement gate the same
        // source of truth, so they can never drift apart.
        Schema::create('claude_daily_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->string('phone');
            $table->date('date');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['whatsapp_account_id', 'phone', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claude_daily_usages');
    }
};
