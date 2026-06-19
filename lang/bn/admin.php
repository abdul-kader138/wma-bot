<?php

return [

    'nav' => [
        'dashboard'        => 'ড্যাশবোর্ড',
        'services'         => 'সেবাসমূহ',
        'service_requests' => 'সেবা অনুরোধ',
        'conversations'    => 'কথোপকথন',
        'faqs'             => 'সাধারণ প্রশ্ন',
        'users'            => 'ব্যবহারকারী',
        'roles'            => 'ভূমিকা',
        'system_settings'  => 'সিস্টেম সেটিংস',
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
            'phone'        => 'হোয়াটসঅ্যাপ ফোন',
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
        'fields' => [
            'phone'         => 'ফোন',
            'last_activity' => 'শেষ কার্যকলাপ',
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

    'settings' => [
        'title'   => 'সিস্টেম সেটিংস',
        'save'    => 'সেটিংস সংরক্ষণ করুন',
        'saved'   => 'সেটিংস সংরক্ষিত হয়েছে। থিম পরিবর্তন প্রয়োগ করতে পৃষ্ঠাটি রিফ্রেশ করুন।',
        'tabs' => [
            'general'    => 'সাধারণ',
            'appearance' => 'চেহারা',
            'whatsapp'   => 'হোয়াটসঅ্যাপ',
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
            'wa_api'       => 'WhatsApp Business API',
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
        'recent' => [
            'heading' => 'সাম্প্রতিক অনুরোধ',
        ],
    ],

    'profile' => [
        'sections' => [
            'picture'  => 'প্রোফাইল ছবি',
            'details'  => 'ব্যক্তিগত তথ্য',
            'security' => 'পাসওয়ার্ড পরিবর্তন',
        ],
        'descriptions' => [
            'picture'  => 'একটি বর্গাকার ছবি আপলোড করুন। এটি বৃত্তাকারে প্রদর্শিত হবে।',
            'details'  => 'আপনার নাম এবং ইমেইল আপডেট করুন।',
            'security' => 'বর্তমান পাসওয়ার্ড রাখতে ফাঁকা রাখুন।',
        ],
        'fields' => [
            'avatar' => 'অবতার',
        ],
    ],

    'brand' => [
        'headline_dark'  => 'আপনার :name নিয়ন্ত্রণ কেন্দ্র।',
        'headline_light' => 'আত্মবিশ্বাসের সাথে আপনার AI বট পরিচালনা করুন।',
        'admin_badge'    => 'অ্যাডমিন',
        'footer'         => 'Claude AI ও WhatsApp Business API দ্বারা পরিচালিত',
        'features' => [
            'AI-চালিত WhatsApp কথোপকথন',
            'স্মার্ট FAQ ম্যাচিং ও রাউটিং',
            'রিয়েল-টাইম সেবা অনুরোধ ব্যবস্থাপনা',
            'সম্পূর্ণ কথোপকথন ইতিহাস ও বিশ্লেষণ',
        ],
    ],

];
