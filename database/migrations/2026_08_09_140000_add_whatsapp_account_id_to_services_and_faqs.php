<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $t) {
            $t->foreignId('whatsapp_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('faqs', function (Blueprint $t) {
            $t->foreignId('whatsapp_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $defaultAccountId = DB::table('whatsapp_accounts')->where('is_default', true)->value('id')
            ?? DB::table('whatsapp_accounts')->orderBy('id')->value('id');

        if ($defaultAccountId) {
            DB::table('services')->update(['whatsapp_account_id' => $defaultAccountId]);
            DB::table('faqs')->update(['whatsapp_account_id' => $defaultAccountId]);
        }

        // Each account now has its own catalog, so the same slug can be reused
        // across different accounts — only unique within one account.
        Schema::table('services', function (Blueprint $t) {
            $t->dropUnique(['slug']);
            $t->unique(['slug', 'whatsapp_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $t) {
            $t->dropUnique(['slug', 'whatsapp_account_id']);
            $t->unique('slug');
            $t->dropConstrainedForeignId('whatsapp_account_id');
        });

        Schema::table('faqs', function (Blueprint $t) {
            $t->dropConstrainedForeignId('whatsapp_account_id');
        });
    }
};
