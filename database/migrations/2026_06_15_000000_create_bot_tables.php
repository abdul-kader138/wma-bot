<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->string('wa_phone')->unique();
            $t->string('step')->default('NEW');
            $t->string('language')->nullable();
            $t->string('service')->nullable();
            $t->json('history')->nullable();
            $t->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $t) {
            $t->id();
            $t->string('wa_phone')->index();
            $t->string('service');
            $t->json('payload');
            $t->string('status')->default('new');
            $t->text('staff_notes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('conversations');
    }
};
