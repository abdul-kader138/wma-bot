<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone_number_id')->unique();
            $t->string('waba_id')->nullable();
            $t->text('access_token')->nullable();
            $t->string('api_version')->default('v22.0');
            $t->boolean('is_active')->default(true);
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });

        $this->seedDefaultAccountFromExistingSettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }

    private function seedDefaultAccountFromExistingSettings(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = DB::table('settings')
            ->whereIn('key', ['whatsapp_phone_number_id', 'whatsapp_access_token', 'whatsapp_api_version'])
            ->pluck('value', 'key');

        $phoneNumberId = $settings['whatsapp_phone_number_id'] ?? null;

        if (blank($phoneNumberId)) {
            return;
        }

        DB::table('whatsapp_accounts')->insert([
            'name'            => 'Default',
            'phone_number_id' => $phoneNumberId,
            'access_token'    => filled($settings['whatsapp_access_token'] ?? null)
                ? Crypt::encryptString($settings['whatsapp_access_token'])
                : null,
            'api_version'     => $settings['whatsapp_api_version'] ?? 'v22.0',
            'is_active'       => true,
            'is_default'      => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
};
