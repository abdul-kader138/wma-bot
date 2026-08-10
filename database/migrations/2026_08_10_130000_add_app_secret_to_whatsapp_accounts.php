<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lets a single account override the platform-wide WHATSAPP_APP_SECRET /
    // MESSENGER_APP_SECRET / INSTAGRAM_APP_SECRET from .env — needed when that
    // account's number/page/IG account lives under a different Meta App (e.g. a
    // separate Meta Business account) than the rest, since Meta's webhook payload
    // signature is computed per-App, not per-number. Nullable: leaving it unset
    // keeps using the shared .env secret, so this changes nothing for the common
    // single-App setup.
    public function up(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $t) {
            $t->text('app_secret')->nullable()->after('access_token');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $t) {
            $t->dropColumn('app_secret');
        });
    }
};
