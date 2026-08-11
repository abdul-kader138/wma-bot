<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_whatsapp_account', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_id', 'whatsapp_account_id']);
        });

        DB::table('services')
            ->whereNotNull('whatsapp_account_id')
            ->orderBy('id')
            ->chunkById(500, function ($services) {
                DB::table('service_whatsapp_account')->insertOrIgnore(
                    $services->map(fn ($service) => [
                        'service_id' => $service->id,
                        'whatsapp_account_id' => $service->whatsapp_account_id,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_whatsapp_account');
    }
};
