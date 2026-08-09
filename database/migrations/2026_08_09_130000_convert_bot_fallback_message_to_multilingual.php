<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('settings')->where('key', 'bot_fallback_message')->first();

        if (! $setting) {
            return;
        }

        $decoded = json_decode((string) $setting->value, true);
        $en      = is_array($decoded) ? ($decoded['en'] ?? '') : $setting->value;
        $en      = $en ?: "I'm sorry, I don't understand. Please contact support.";

        DB::table('settings')->where('key', 'bot_fallback_message')->update([
            'value' => json_encode([
                'en' => $en,
                'it' => 'Mi dispiace, non ho capito. Contatta il nostro supporto.',
                'bn' => 'দুঃখিত, আমি বুঝতে পারিনি। আমাদের সাপোর্ট টিমের সাথে যোগাযোগ করুন।',
            ]),
            'type' => 'json',
        ]);

        Cache::forget('setting:bot_fallback_message');
    }

    public function down(): void
    {
        $setting = DB::table('settings')->where('key', 'bot_fallback_message')->first();

        if (! $setting) {
            return;
        }

        $data = json_decode((string) $setting->value, true) ?? [];

        DB::table('settings')->where('key', 'bot_fallback_message')->update([
            'value' => $data['en'] ?? "I'm sorry, I don't understand. Please contact support.",
            'type'  => 'text',
        ]);

        Cache::forget('setting:bot_fallback_message');
    }
};
