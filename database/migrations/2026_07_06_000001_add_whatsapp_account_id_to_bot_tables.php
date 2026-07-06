<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $t) {
            $t->foreignId('whatsapp_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('service_requests', function (Blueprint $t) {
            $t->foreignId('whatsapp_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $defaultAccountId = DB::table('whatsapp_accounts')->where('is_default', true)->value('id');

        if ($defaultAccountId) {
            DB::table('conversations')->update(['whatsapp_account_id' => $defaultAccountId]);
            DB::table('service_requests')->update(['whatsapp_account_id' => $defaultAccountId]);
        }

        Schema::table('conversations', function (Blueprint $t) {
            $t->dropUnique(['wa_phone']);
            $t->unique(['wa_phone', 'whatsapp_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $t) {
            $t->dropUnique(['wa_phone', 'whatsapp_account_id']);
            $t->unique('wa_phone');
            $t->dropConstrainedForeignId('whatsapp_account_id');
        });

        Schema::table('service_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('whatsapp_account_id');
        });
    }
};
