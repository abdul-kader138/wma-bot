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
            ['group' => 'whatsapp', 'key' => 'whatsapp_verify_token', 'value' => '', 'type' => 'string', 'label' => 'Webhook Verify Token', 'is_public' => false],

            // Claude AI
            ['group' => 'claude', 'key' => 'claude_api_key',     'value' => '',                        'type' => 'string',  'label' => 'API Key',       'is_public' => false],
            ['group' => 'claude', 'key' => 'claude_model',       'value' => 'claude-haiku-4-5-20251001', 'type' => 'string', 'label' => 'Model',        'is_public' => false],
            ['group' => 'claude', 'key' => 'claude_max_tokens',  'value' => '1024',                    'type' => 'integer', 'label' => 'Max Tokens',    'is_public' => false],
            ['group' => 'claude', 'key' => 'claude_temperature', 'value' => '0.7',                     'type' => 'float',   'label' => 'Temperature',   'is_public' => false],
            ['group' => 'claude', 'key' => 'claude_rate_limit_per_minute', 'value' => '50', 'type' => 'integer', 'label' => 'Rate Limit (requests/min)', 'is_public' => false],

            // Bot Behaviour
            ['group' => 'bot', 'key' => 'faq_confidence_threshold', 'value' => '0.7',                                          'type' => 'float',  'label' => 'FAQ Confidence Threshold', 'is_public' => false],
            ['group' => 'bot', 'key' => 'knowledge_max_distance',   'value' => '0.4',                                          'type' => 'float',  'label' => 'Knowledge Base Match Distance', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_welcome_message',      'value' => json_encode([
                'en' => 'Hello! How can I help you today?',
                'it' => 'Ciao! Come posso aiutarti oggi?',
                'bn' => 'হ্যালো! আজ আমি আপনাকে কীভাবে সাহায্য করতে পারি?',
            ]), 'type' => 'json', 'label' => 'Welcome Message', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_fallback_message',     'value' => json_encode([
                'en' => "I'm sorry, I don't understand. Please contact support.",
                'it' => 'Mi dispiace, non ho capito. Contatta il nostro supporto.',
                'bn' => 'দুঃখিত, আমি বুঝতে পারিনি। আমাদের সাপোর্ট টিমের সাথে যোগাযোগ করুন।',
            ]), 'type' => 'json', 'label' => 'Fallback Message', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_close_conversation_message', 'value' => json_encode([
                'en' => 'Thanks for chatting! If you need anything else, just message us again anytime.',
                'it' => 'Grazie per la chat! Se hai bisogno di altro, scrivici di nuovo in qualsiasi momento.',
                'bn' => 'চ্যাট করার জন্য ধন্যবাদ! আরও কিছু লাগলে যেকোনো সময় আবার মেসেজ করুন।',
            ]), 'type' => 'json', 'label' => 'Close Conversation Message', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_escalation_message', 'value' => json_encode([
                'en' => "We've let our team know you'd like to speak with a person. Someone will be with you shortly.",
                'it' => 'Abbiamo avvisato il nostro team che vorresti parlare con una persona. Qualcuno ti risponderà a breve.',
                'bn' => 'আমরা আমাদের টিমকে জানিয়েছি যে আপনি একজন মানুষের সাথে কথা বলতে চান। শীঘ্রই কেউ আপনার সাথে যোগাযোগ করবে।',
            ]), 'type' => 'json', 'label' => 'Escalation Message', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_rating_thanks_message', 'value' => json_encode([
                'en' => 'Thanks for the feedback!',
                'it' => 'Grazie per il tuo feedback!',
                'bn' => 'ফিডব্যাকের জন্য ধন্যবাদ!',
            ]), 'type' => 'json', 'label' => 'Rating Thanks Message', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_idle_follow_up_message', 'value' => json_encode([
                'en' => 'Still there? Let us know if you need anything else, or type "menu" to start over.',
                'it' => 'Ci sei ancora? Fammi sapere se hai bisogno di altro, oppure scrivi "menu" per ricominciare.',
                'bn' => 'আপনি কি এখনও আছেন? আর কিছু লাগলে জানান, অথবা নতুন করে শুরু করতে "menu" লিখুন।',
            ]), 'type' => 'json', 'label' => 'Idle Follow-up Message', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_idle_follow_up_hours', 'value' => '4', 'type' => 'integer', 'label' => 'Idle Follow-up Hours', 'is_public' => false],
            ['group' => 'bot', 'key' => 'knowledge_stale_after_months', 'value' => '6', 'type' => 'integer', 'label' => 'Knowledge Staleness (months)', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_opt_out_message', 'value' => json_encode([
                'en' => "You've been unsubscribed and won't receive further messages from us. Message us anytime to start again.",
                'it' => 'Sei stato disiscritto e non riceverai più messaggi da noi. Scrivici in qualsiasi momento per ricominciare.',
                'bn' => 'আপনি আনসাবস্ক্রাইব করেছেন এবং আর আমাদের থেকে মেসেজ পাবেন না। পুনরায় শুরু করতে যেকোনো সময় আমাদের মেসেজ করুন।',
            ]), 'type' => 'json', 'label' => 'Opt-out Confirmation Message', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_unsupported_message', 'value' => json_encode([
                'en' => "Sorry, I can't read that type of message yet. Please describe what you need in text, or type \"human\" to talk to our team.",
                'it' => 'Spiacenti, non posso ancora leggere questo tipo di messaggio. Descrivi cosa ti serve in un messaggio di testo, oppure scrivi "human" per parlare con il nostro team.',
                'bn' => 'দুঃখিত, আমি এখনও এই ধরনের মেসেজ পড়তে পারি না। অনুগ্রহ করে টেক্সটে বলুন কী প্রয়োজন, অথবা আমাদের টিমের সাথে কথা বলতে "human" লিখুন।',
            ]), 'type' => 'json', 'label' => 'Unsupported Message Type Reply', 'is_public' => false],
            ['group' => 'bot', 'key' => 'bot_escalation_after_hours_message', 'value' => json_encode([
                'en' => "We've let our team know you'd like to speak with a person. We're outside business hours right now, but someone will respond as soon as we're back.",
                'it' => "Abbiamo avvisato il nostro team che vorresti parlare con una persona. Al momento siamo fuori orario, ma qualcuno ti risponderà appena possibile.",
                'bn' => 'আমরা আমাদের টিমকে জানিয়েছি যে আপনি একজন মানুষের সাথে কথা বলতে চান। এখন আমাদের কর্মঘণ্টার বাইরে, তবে ফিরে আসার সাথে সাথে কেউ উত্তর দেবে।',
            ]), 'type' => 'json', 'label' => 'After-hours Escalation Message', 'is_public' => false],
            ['group' => 'bot', 'key' => 'business_hours_enabled', 'value' => '0', 'type' => 'boolean', 'label' => 'Business Hours Enabled', 'is_public' => false],
            ['group' => 'bot', 'key' => 'business_hours_timezone', 'value' => 'UTC', 'type' => 'string', 'label' => 'Business Hours Timezone', 'is_public' => false],
            ['group' => 'bot', 'key' => 'business_hours_start', 'value' => '09:00', 'type' => 'string', 'label' => 'Business Hours Start', 'is_public' => false],
            ['group' => 'bot', 'key' => 'business_hours_end', 'value' => '18:00', 'type' => 'string', 'label' => 'Business Hours End', 'is_public' => false],
            ['group' => 'bot', 'key' => 'business_hours_days', 'value' => json_encode(['mon', 'tue', 'wed', 'thu', 'fri']), 'type' => 'json', 'label' => 'Business Hours Days', 'is_public' => false],

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
