<?php

return [

    'auth' => [
        'two_factor' => [
            'heading'       => 'দ্বি-স্তর যাচাইকরণ',
            'code_label'    => 'যাচাইকরণ কোড',
            'code_helper'   => 'আপনার অথেনটিকেটর অ্যাপের ৬-সংখ্যার কোড অথবা একটি রিকভারি কোড লিখুন।',
            'back_to_login' => 'লগইনে ফিরে যান',
        ],
    ],

    'nav' => [
        'dashboard'        => 'ড্যাশবোর্ড',
        'services'         => 'সেবাসমূহ',
        'service_requests' => 'সেবা অনুরোধ',
        'conversations'    => 'কথোপকথন',
        'faqs'             => 'সাধারণ প্রশ্ন',
        'documents'        => 'ডকুমেন্টস',
        'users'            => 'ব্যবহারকারী',
        'roles'            => 'ভূমিকা',
        'system_settings'  => 'সিস্টেম সেটিংস',
        'whatsapp_accounts' => 'মেসেজিং অ্যাকাউন্ট',
        'groups' => [
            'administration' => 'প্রশাসন',
        ],
    ],

    'stats' => [
        'new_requests'        => 'নতুন অনুরোধ',
        'awaiting_staff'      => 'কর্মীর পদক্ষেপের অপেক্ষায়',
        'in_progress'         => 'প্রক্রিয়াধীন',
        'being_handled'       => 'পরিচালনা করা হচ্ছে',
        'completed'           => 'সম্পন্ন',
        'all_time'            => 'সর্বকালীন',
        'active_conversations'=> 'সক্রিয় কথোপকথন',
        'open_chats'          => 'বর্তমানে খোলা চ্যাট',
    ],

    'service' => [
        'label'        => 'সেবা',
        'label_plural' => 'সেবাসমূহ',
        'tabs' => [
            'basic'  => 'মূল তথ্য',
            'bot'    => 'বট সেটিংস',
            'fields' => 'ডেটা ক্ষেত্র',
        ],
        'sections' => [
            'identity'   => 'পরিচয়',
            'labels'     => 'বহুভাষিক লেবেল',
            'bot_config' => 'বট কনফিগারেশন',
            'fields'     => 'ডেটা ক্ষেত্র',
        ],
        'fields' => [
            'label_en'         => 'সেবার নাম',
            'color'            => 'ব্যাজের রঙ',
            'is_active'        => 'সক্রিয়',
            'sort_order'       => 'ক্রম',
            'prompt_label'     => 'প্রম্পট লেবেল',
            'tool_name'        => 'টুল ফাংশনের নাম',
            'tool_description' => 'টুলের বিবরণ',
        ],
    ],

    'service_request' => [
        'label'        => 'সেবা অনুরোধ',
        'label_plural' => 'সেবা অনুরোধসমূহ',
        'sections' => [
            'details' => 'অনুরোধের বিবরণ',
        ],
        'fields' => [
            'phone'        => 'ফোন / যোগাযোগ আইডি',
            'service'      => 'সেবা',
            'status'       => 'অবস্থা',
            'payload'      => 'সংগৃহীত তথ্য',
            'staff_notes'  => 'কর্মী নোট',
            'phone_short'  => 'ফোন',
            'received'     => 'প্রাপ্ত',
            'last_updated' => 'সর্বশেষ আপডেট',
        ],
        'status' => [
            'new'         => 'নতুন',
            'in_progress' => 'প্রক্রিয়াধীন',
            'done'        => 'সম্পন্ন',
        ],
        'actions' => [
            'in_progress'  => 'প্রক্রিয়াধীন',
            'done'         => 'সম্পন্ন',
            'mark_as_done' => 'সম্পন্ন হিসেবে চিহ্নিত করুন',
        ],
    ],

    'conversation' => [
        'label'        => 'কথোপকথন',
        'label_plural' => 'কথোপকথনসমূহ',
        'sections' => [
            'details'    => 'বিস্তারিত',
            'transcript' => 'কথোপকথনের বিবরণ',
        ],
        'fields' => [
            'phone'         => 'ফোন',
            'last_activity' => 'শেষ কার্যকলাপ',
            'started_at'    => 'শুরু হয়েছে',
            'language'      => 'ভাষা',
            'role'          => 'প্রেরক',
            'message'       => 'বার্তা',
        ],
        'roles' => [
            'user'      => 'গ্রাহক',
            'assistant' => 'বট',
        ],
        'steps' => [
            'NEW'           => 'নতুন',
            'AWAIT_LANG'    => 'ভাষার অপেক্ষায়',
            'AWAIT_SERVICE' => 'সেবার অপেক্ষায়',
            'IN_SERVICE'    => 'সেবায়',
            'DONE'          => 'সম্পন্ন',
        ],
        'actions' => [
            'reset' => 'পুনরায় সেট করুন',
        ],
    ],

    'faq' => [
        'label'        => 'সাধারণ প্রশ্ন',
        'label_plural' => 'সাধারণ প্রশ্নাবলী',
        'sections' => [
            'faq'    => 'সাধারণ প্রশ্ন',
            'answer' => 'উত্তর',
        ],
        'fields' => [
            'applies_to'   => 'প্রযোজ্য',
            'all_services' => 'সকল সেবা',
            'active'       => 'সক্রিয়',
            'question'     => 'রেফারেন্স প্রশ্ন (কর্মীদের জন্য)',
            'keywords'     => 'ট্রিগার বাক্যাংশ',
            'keywords_help'=> 'শব্দ বা ছোট বাক্যাংশ যা এই উত্তরটি ট্রিগার করবে, যেমন "দাম", "কত", "খোলার সময়"।',
            'triggers'     => 'ট্রিগার',
        ],
    ],

    'user' => [
        'label'        => 'ব্যবহারকারী',
        'label_plural' => 'ব্যবহারকারীগণ',
        'sections' => [
            'account' => 'অ্যাকাউন্টের বিবরণ',
            'roles'   => 'ভূমিকা ও অনুমতি',
        ],
        'fields' => [
            'name'             => 'নাম',
            'email'            => 'ইমেইল',
            'password'         => 'পাসওয়ার্ড',
            'confirm_password' => 'পাসওয়ার্ড নিশ্চিত করুন',
            'password_help'    => 'বর্তমান পাসওয়ার্ড রাখতে ফাঁকা রাখুন (সম্পাদনার সময়)।',
            'roles'            => 'ভূমিকা',
            'roles_help'       => 'এক বা একাধিক ভূমিকা নির্ধারণ করুন। ভূমিকাগুলো নিয়ন্ত্রণ করে এই ব্যবহারকারী অ্যাডমিন প্যানেলে কী দেখতে ও করতে পারবে।',
            'verified'         => 'যাচাইকৃত',
            'not_verified'     => 'যাচাই হয়নি',
            'role'             => 'ভূমিকা',
        ],
    ],

    'whatsapp_account' => [
        'label'        => 'মেসেজিং অ্যাকাউন্ট',
        'label_plural' => 'মেসেজিং অ্যাকাউন্টসমূহ',
        'sections' => [
            'identity'    => 'পরিচয়',
            'credentials' => 'Meta API ক্রেডেনশিয়াল',
        ],
        'fields' => [
            'name'             => 'নাম',
            'name_help'        => 'এই নম্বরটি শনাক্ত করতে একটি বন্ধুত্বপূর্ণ লেবেল, যেমন "সেলস" বা "সাপোর্ট"।',
            'phone_number_id'  => 'ফোন নম্বর আইডি',
            'phone_number_id_help' => 'Meta Business Suite → WhatsApp → API Setup-এ পাওয়া যাবে।',
            'waba_id'          => 'হোয়াটসঅ্যাপ বিজনেস অ্যাকাউন্ট আইডি',
            'access_token'     => 'অ্যাক্সেস টোকেন',
            'access_token_help'=> 'এই নম্বরের জন্য Meta থেকে স্থায়ী বা সাময়িক অ্যাক্সেস টোকেন।',
            'api_version'      => 'API সংস্করণ',
            'is_active'        => 'সক্রিয়',
            'is_active_help'   => 'নিষ্ক্রিয় অ্যাকাউন্ট হোয়াটসঅ্যাপ বার্তা পাঠাতে বা গ্রহণ করতে পারবে না।',
            'is_default'       => 'ডিফল্ট',
            'is_default_help'  => 'কোনো নম্বর অন্যভাবে শনাক্ত করা না গেলে ফলব্যাক হিসেবে ব্যবহৃত হয়।',
        ],
    ],

    'settings' => [
        'title'   => 'সিস্টেম সেটিংস',
        'save'    => 'সেটিংস সংরক্ষণ করুন',
        'saved'   => 'সেটিংস সংরক্ষিত হয়েছে। থিম পরিবর্তন প্রয়োগ করতে পৃষ্ঠাটি রিফ্রেশ করুন।',
        'tabs' => [
            'general'    => 'সাধারণ',
            'appearance' => 'চেহারা',
            'security'   => 'নিরাপত্তা',
            'whatsapp'   => 'মেসেজিং চ্যানেল',
            'claude'     => 'ক্লড AI',
            'bot'        => 'বট আচরণ',
            'email'      => 'ইমেইল',
        ],
        'sections' => [
            'application'  => 'অ্যাপ্লিকেশন',
            'color_theme'  => 'রঙের থিম',
            'panel_mode'   => 'প্যানেল মোড',
            'auth_bg'      => 'লগইন পৃষ্ঠার পটভূমি',
            'branding'     => 'ব্র্যান্ডিং সম্পদ',
            'two_factor'   => 'দ্বি-স্তর যাচাইকরণ',
            'wa_api'       => 'WhatsApp Business API',
            'messenger_api' => 'Facebook Messenger',
            'instagram_api' => 'Instagram',
            'claude_api'   => 'Anthropic Claude API',
            'response'     => 'প্রতিক্রিয়া সেটিংস',
            'mail_sender'  => 'মেইল প্রেরক',
        ],
        'fields' => [
            'app_name'         => 'অ্যাপ্লিকেশনের নাম',
            'app_tagline'      => 'ট্যাগলাইন',
            'support_email'    => 'সহায়তা ইমেইল',
            'maintenance_mode'        => 'রক্ষণাবেক্ষণ মোড',
            'maintenance_help'        => 'সক্রিয় থাকলে অ্যাডমিন প্যানেলে রক্ষণাবেক্ষণ বিজ্ঞপ্তি দেখাবে।',
            'default_language'        => 'ডিফল্ট ভাষা',
            'default_language_help'   => 'লগইন পৃষ্ঠায় এবং প্যানেলের ডিফল্ট হিসেবে যে ভাষা দেখানো হবে। ব্যক্তিগত অ্যাডমিনরা উপরের সিলেক্টর থেকে ভাষা পরিবর্তন করতে পারবেন।',
            'two_factor_enabled'      => 'দ্বি-স্তর যাচাইকরণ সক্রিয় করুন',
            'two_factor_enabled_help' => 'পুরো অ্যাডমিন প্যানেলের জন্য দ্বি-স্তর যাচাইকরণ চালু বা বন্ধ করে। বন্ধ থাকলে লগইনের সময় কাউকে কোড চাওয়া হবে না এবং অ্যাডমিনরা তাদের প্রোফাইল থেকে এটি সক্রিয় করতে পারবেন না — বিদ্যমান সেটআপ থেকে যাবে তবে এটি আবার চালু না করা পর্যন্ত নিষ্ক্রিয় থাকবে।',
        ],
    ],

    'dashboard' => [
        'greeting' => [
            'morning'   => 'শুভ সকাল',
            'afternoon' => 'শুভ দুপুর',
            'evening'   => 'শুভ সন্ধ্যা',
        ],
        'quick' => [
            'pending' => 'অপেক্ষারত',
            'active'  => 'সক্রিয় চ্যাট',
            'today'   => 'আজ',
        ],
        'chart' => [
            'heading'       => 'সেবা অনুরোধ',
            'last_7_days'   => 'শেষ ৭ দিন',
            'last_14_days'  => 'শেষ ১৪ দিন',
            'last_30_days'  => 'শেষ ৩০ দিন',
            'dataset_label' => 'অনুরোধ',
        ],
        'conversations_chart' => [
            'heading'       => 'কথোপকথন',
            'last_7_days'   => 'শেষ ৭ দিন',
            'last_14_days'  => 'শেষ ১৪ দিন',
            'last_30_days'  => 'শেষ ৩০ দিন',
            'dataset_label' => 'কথোপকথন',
        ],
        'recent' => [
            'heading' => 'সাম্প্রতিক অনুরোধ',
        ],
    ],

    'profile' => [
        'sections' => [
            'picture'    => 'প্রোফাইল ছবি',
            'details'    => 'ব্যক্তিগত তথ্য',
            'security'   => 'পাসওয়ার্ড পরিবর্তন',
            'two_factor' => 'দ্বি-স্তর যাচাইকরণ',
        ],
        'descriptions' => [
            'picture'    => 'একটি বর্গাকার ছবি আপলোড করুন। এটি বৃত্তাকারে প্রদর্শিত হবে।',
            'details'    => 'আপনার নাম এবং ইমেইল আপডেট করুন।',
            'security'   => 'বর্তমান পাসওয়ার্ড রাখতে ফাঁকা রাখুন।',
            'two_factor' => 'অথেনটিকেটর অ্যাপ ব্যবহার করে আপনার অ্যাকাউন্টে অতিরিক্ত সুরক্ষা যোগ করুন।',
        ],
        'fields' => [
            'avatar' => 'অবতার',
        ],
        'two_factor' => [
            'enabled'                  => 'দ্বি-স্তর যাচাইকরণ সক্রিয় আছে।',
            'disabled'                 => 'দ্বি-স্তর যাচাইকরণ সক্রিয় নেই।',
            'disabled_globally'        => 'একজন অ্যাডমিন এই অ্যাপ্লিকেশনের জন্য দ্বি-স্তর যাচাইকরণ বন্ধ করে দিয়েছেন।',
            'enable_action'            => 'সক্রিয় করুন',
            'disable_action'           => 'নিষ্ক্রিয় করুন',
            'disable_confirm_heading'  => 'দ্বি-স্তর যাচাইকরণ নিষ্ক্রিয় করবেন?',
            'disable_confirm_body'     => 'সাইন ইন করার সময় আর কোড চাওয়া হবে না।',
            'show_recovery_codes'      => 'রিকভারি কোড দেখুন',
            'regenerate_recovery_codes' => 'রিকভারি কোড পুনরায় তৈরি করুন',
            'setup_heading'            => 'QR কোড স্ক্যান করুন',
            'setup_description'        => 'আপনার অথেনটিকেটর অ্যাপ (Google Authenticator, Authy, 1Password ইত্যাদি) দিয়ে এই QR কোডটি স্ক্যান করুন, তারপর নিশ্চিত করতে জেনারেট হওয়া ৬-সংখ্যার কোডটি দিন।',
            'secret_label'             => 'অথবা এই কোডটি ম্যানুয়ালি লিখুন',
            'code_label'               => 'নিশ্চিতকরণ কোড',
            'code_helper'              => 'আপনার অথেনটিকেটর অ্যাপের ৬-সংখ্যার কোডটি লিখুন।',
            'invalid_code'             => 'প্রদত্ত কোডটি সঠিক নয়।',
            'confirm_action'           => 'নিশ্চিত করুন',
            'recovery_codes_heading'   => 'রিকভারি কোড',
            'recovery_codes_description' => 'এই কোডগুলো নিরাপদ স্থানে সংরক্ষণ করুন। অথেনটিকেটর অ্যাপ হারিয়ে গেলে প্রতিটি কোড একবার ব্যবহার করে সাইন ইন করা যাবে।',
            'enabled_notification'    => 'দ্বি-স্তর যাচাইকরণ সক্রিয় হয়েছে।',
            'disabled_notification'   => 'দ্বি-স্তর যাচাইকরণ নিষ্ক্রিয় হয়েছে।',
            'regenerated_notification' => 'রিকভারি কোড পুনরায় তৈরি হয়েছে।',
        ],
    ],

    'brand' => [
        'headline_dark'  => 'আপনার :name নিয়ন্ত্রণ কেন্দ্র।',
        'headline_light' => 'আত্মবিশ্বাসের সাথে আপনার AI বট পরিচালনা করুন।',
        'admin_badge'    => 'অ্যাডমিন',
        'footer'         => 'Claude AI ও Meta Messaging API দ্বারা পরিচালিত',
        'features' => [
            'AI-চালিত WhatsApp, Messenger ও Instagram কথোপকথন',
            'স্মার্ট FAQ ম্যাচিং ও রাউটিং',
            'রিয়েল-টাইম সেবা অনুরোধ ব্যবস্থাপনা',
            'সম্পূর্ণ কথোপকথন ইতিহাস ও বিশ্লেষণ',
        ],
    ],

];
