<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maria_eval_runs')) {
            return;
        }

        Schema::create('maria_eval_runs', function (Blueprint $table) {
            $table->id();
            $table->string('prompt_type', 100)->default('maria_system');
            $table->string('prompt_version', 50)->nullable();
            $table->string('prompt_content_hash', 64)->index();
            $table->unsignedSmallInteger('total')->default(0);
            $table->unsignedSmallInteger('passed')->default(0);
            $table->unsignedSmallInteger('failed')->default(0);
            $table->decimal('pass_rate', 5, 1)->default(0);
            $table->json('results')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['prompt_content_hash', 'pass_rate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maria_eval_runs');
    }
};
