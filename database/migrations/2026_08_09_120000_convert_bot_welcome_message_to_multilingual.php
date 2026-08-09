<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('settings')->where('key', 'bot_welcome_message')->first();

        if (! $setting) {
            return;
        }

        $decoded = json_decode((string) $setting->value, true);
        $en      = is_array($decoded) ? ($decoded['en'] ?? '') : $setting->value;
        $en      = $en ?: 'Hello! How can I help you today?';

        DB::table('settings')->where('key', 'bot_welcome_message')->update([
            'value' => json_encode([
                'en' => $en,
                'it' => 'Ciao! Come posso aiutarti oggi?',
                'bn' => 'হ্যালো! আজ আমি আপনাকে কীভাবে সাহায্য করতে পারি?',
            ]),
            'type' => 'json',
        ]);

        Cache::forget('setting:bot_welcome_message');
    }

    public function down(): void
    {
        $setting = DB::table('settings')->where('key', 'bot_welcome_message')->first();

        if (! $setting) {
            return;
        }

        $data = json_decode((string) $setting->value, true) ?? [];

        DB::table('settings')->where('key', 'bot_welcome_message')->update([
            'value' => $data['en'] ?? 'Hello! How can I help you today?',
            'type'  => 'text',
        ]);

        Cache::forget('setting:bot_welcome_message');
    }
};
