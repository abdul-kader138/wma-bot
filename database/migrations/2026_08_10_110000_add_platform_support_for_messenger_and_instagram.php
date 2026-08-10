<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Purely additive: every new column defaults to 'whatsapp' (or is nullable), so
    // existing rows and every existing query against these tables keep working exactly
    // as before — nothing here changes behavior for the live WhatsApp path. Messenger
    // and Instagram accounts reuse the whatsapp_accounts table (see WhatsAppAccount's
    // updated doc comment) rather than getting sibling tables, so conversations/service
    // requests/usage stay in one place across every channel instead of fragmenting.
    public function up(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $t) {
            $t->string('platform')->default('whatsapp')->after('id');
            // Messenger Page ID / Instagram Business Account ID. WhatsApp accounts keep
            // using phone_number_id; this column is null for them.
            $t->string('external_id')->nullable()->after('phone_number_id');
            $t->index(['platform', 'external_id']);
        });

        // phone_number_id was NOT NULL + unique when every row was a WhatsApp account;
        // Messenger/Instagram rows don't have one, so it has to become nullable. A unique
        // index still allows any number of NULLs (NULLs are never considered equal to
        // each other), so WhatsApp's uniqueness guarantee is unaffected.
        Schema::table('whatsapp_accounts', function (Blueprint $t) {
            $t->string('phone_number_id')->nullable()->change();
        });

        Schema::table('conversations', function (Blueprint $t) {
            $t->string('platform')->default('whatsapp')->after('whatsapp_account_id');
        });

        Schema::table('service_requests', function (Blueprint $t) {
            $t->string('platform')->default('whatsapp')->after('whatsapp_account_id');
        });

        Schema::table('claude_daily_usages', function (Blueprint $t) {
            $t->string('platform')->default('whatsapp')->after('whatsapp_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $t) {
            $t->string('phone_number_id')->nullable(false)->change();
        });

        Schema::table('whatsapp_accounts', function (Blueprint $t) {
            $t->dropIndex(['platform', 'external_id']);
            $t->dropColumn(['platform', 'external_id']);
        });

        Schema::table('conversations', function (Blueprint $t) {
            $t->dropColumn('platform');
        });

        Schema::table('service_requests', function (Blueprint $t) {
            $t->dropColumn('platform');
        });

        Schema::table('claude_daily_usages', function (Blueprint $t) {
            $t->dropColumn('platform');
        });
    }
};
