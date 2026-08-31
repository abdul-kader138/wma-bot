<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assistant_conversations', 'summary')) {
            Schema::table('assistant_conversations', function (Blueprint $table) {
                $table->text('summary')->nullable()->after('history');
            });
        }

        if (! Schema::hasTable('assistant_memories')) {
            Schema::create('assistant_memories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('key');
                $table->text('value');
                $table->string('source', 30)->default('chat');
                $table->timestamps();

                $table->unique(['user_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_memories');
        if (Schema::hasColumn('assistant_conversations', 'summary')) {
            Schema::table('assistant_conversations', fn (Blueprint $table) => $table->dropColumn('summary'));
        }
    }
};
