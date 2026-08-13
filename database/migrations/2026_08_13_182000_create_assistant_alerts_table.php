<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        Schema::dropIfExists('assistant_alerts');
    }
};
