<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['group' => 'general', 'key' => 'app_name',         'value' => 'WMA Bot',                   'type' => 'string',  'label' => 'Application Name',   'is_public' => true],
            ['group' => 'general', 'key' => 'app_tagline',      'value' => 'WhatsApp AI Assistant',      'type' => 'string',  'label' => 'Tagline',            'is_public' => true],
            ['group' => 'general', 'key' => 'support_email',    'value' => '',                           'type' => 'string',  'label' => 'Support Email',      'is_public' => true],
            ['group' => 'general', 'key' => 'maintenance_mode', 'value' => '0',                          'type' => 'boolean', 'label' => 'Maintenance Mode',   'is_public' => false],
            ['group' => 'general', 'key' => 'admin_locale',     'value' => 'en',                         'type' => 'string',  'label' => 'Default Language',   'is_public' => false],

            // Appearance
            ['group' => 'appearance', 'key' => 'admin_theme',            'value' => 'amber',   'type' => 'string', 'label' => 'Admin Theme',          'is_public' => false],
            ['group' => 'appearance', 'key' => 'admin_panel_theme_mode', 'value' => 'dark',    'type' => 'string', 'label' => 'Panel Mode',            'is_public' => false],
            ['group' => 'appearance', 'key' => 'auth_theme_mode',        'value' => 'dark',    'type' => 'string', 'label' => 'Auth Panel Style',      'is_public' => false],
            ['group' => 'appearance', 'key' => 'auth_background',        'value' => 'inherit', 'type' => 'string', 'label' => 'Auth Background',       'is_public' => false],
            ['group' => 'appearance', 'key' => 'app_logo',               'value' => null,      'type' => 'string', 'label' => 'App Logo',              'is_public' => true],
            ['group' => 'appearance', 'key' => 'app_icon',               'value' => null,      'type' => 'string', 'label' => 'App Icon',              'is_public' => true],
            ['group' => 'appearance', 'key' => 'login_image',            'value' => null,      'type' => 'string', 'label' => 'Login Image',           'is_public' => true],
            ['group' => 'appearance', 'key' => 'favicon',                'value' => null,      'type' => 'string', 'label' => 'Favicon',               'is_public' => true],

            // WhatsApp
            ['group' => 'whatsapp', 'key' => 'whatsapp_phone_number_id', 'value' => '', 'type' => 'string', 'label' => 'Phone Number ID',     'is_public' => false],
            ['group' => 'whatsapp', 'key' => 'whatsapp_access_token',    'value' => '', 'type' => 'string', 'label' => 'Access Token',        'is_public' => false],
            ['group' => 'whatsapp', 'key' => 'whatsapp_verify_token',    'value' => '', 'type' => 'string', 'label' => 'Webhook Verify Token', 'is_public' => false],
            ['group' => 'whatsapp', 'key' => 'whatsapp_api_version',     'value' => 'v22.0', 'type' => 'string', 'label' => 'API Version',    'is_public' => false],

            // Claude AI
            ['group' => 'claude', 'key' => 'claude_api_key',     'value' => '',                        'type' => 'string',  'label' => 'API Key',       'is_public' => false],
            ['group' => 'claude', 'key' => 'claude_model',       'value' => 'claude-haiku-4-5-20251001', 'type' => 'string', 'label' => 'Model',        'is_public' => false],
            ['group' => 'claude', 'key' => 'claude_max_tokens',  'value' => '1024',                    'type' => 'integer', 'label' => 'Max Tokens',    'is_public' => false],
            ['group' => 'claude', 'key' => 'claude_temperature', 'value' => '0.7',                     'type' => 'float',   'label' => 'Temperature',   'is_public' => false],

            // Bot Behaviour
            ['group' => 'bot', 'key' => 'faq_confidence_threshold', 'value' => '0.7',                                          'type' => 'float',  'label' => 'FAQ Confidence Threshold', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_welcome_message',      'value' => 'Hello! How can I help you today?',              'type' => 'text',   'label' => 'Welcome Message',          'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_fallback_message',     'value' => "I'm sorry, I don't understand. Please contact support.", 'type' => 'text', 'label' => 'Fallback Message', 'is_public' => false],

            // Email
            ['group' => 'email', 'key' => 'mail_from_name',    'value' => 'WMA Bot',          'type' => 'string', 'label' => 'From Name'],
            ['group' => 'email', 'key' => 'mail_from_address', 'value' => 'no-reply@wma.bot', 'type' => 'string', 'label' => 'From Address'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Default settings seeded.');
    }
}
