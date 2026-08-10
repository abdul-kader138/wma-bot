<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The (platform, external_id) index added alongside `external_id` wasn't unique,
    // unlike `phone_number_id`'s DB-level unique constraint — so the Filament form's
    // uniqueness check was the only thing stopping two Messenger/Instagram accounts
    // from pointing at the same Page/IG account. Making it unique closes that gap;
    // NULLs (every WhatsApp row) are still unaffected since no DB treats NULL = NULL
    // for uniqueness purposes.
    public function up(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $t) {
            $t->dropIndex(['platform', 'external_id']);
            $t->unique(['platform', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $t) {
            $t->dropUnique(['platform', 'external_id']);
            $t->index(['platform', 'external_id']);
        });
    }
};
