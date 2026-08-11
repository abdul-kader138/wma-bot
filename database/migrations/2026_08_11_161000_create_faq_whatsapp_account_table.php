<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_whatsapp_account', function (Blueprint $table) {
            $table->foreignId('faq_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->primary(['faq_id', 'whatsapp_account_id']);
        });

        DB::table('faqs')
            ->whereNotNull('whatsapp_account_id')
            ->orderBy('id')
            ->chunkById(500, function ($faqs) {
                DB::table('faq_whatsapp_account')->insertOrIgnore(
                    $faqs->map(fn ($faq) => [
                        'faq_id' => $faq->id,
                        'whatsapp_account_id' => $faq->whatsapp_account_id,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_whatsapp_account');
    }
};
