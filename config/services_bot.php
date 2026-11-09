<?php

return [

    'languages' => [
        'en' => 'English',
        'it' => 'Italiano',
        'bn' => 'বাংলা',
    ],

    'replies' => [
        'choose_service' => [
            'en' => 'What can we help you with today?',
            'it' => 'Come possiamo aiutarti oggi?',
            'bn' => 'আজ আমরা কীভাবে সাহায্য করতে পারি?',
        ],
        'confirmation' => [
            'en' => 'Thank you! Your request has been recorded. Our team will get back to you shortly.',
            'it' => 'Grazie! La tua richiesta è stata registrata. Il nostro team ti contatterà a breve.',
            'bn' => 'ধন্যবাদ! আপনার অনুরোধ রেকর্ড করা হয়েছে। আমাদের টিম শীঘ্রই আপনার সাথে যোগাযোগ করবে।',
        ],
        // Button/quick-reply labels attached to every IN_SERVICE reply (see
        // MessagingChannel::sendTextWithQuickActions()) so a customer can switch
        // services or end the chat with one tap instead of needing to type a
        // keyword in the right language. Their ids/payloads are literally "service"
        // and "close_chat", matching the typed keywords HandleIncomingMessage
        // already checks for. No longer sent as buttons (see rate_up_button/
        // rate_down_button/human_handover_button below, which replaced them within
        // WhatsApp's 3-button cap) — kept only as the wording for 'nav_hint' below,
        // so the labels and the typed keywords stay in sync in one place.
        'change_service_button' => [
            'en' => 'Change service',
            'it' => 'Cambia servizio',
            'bn' => 'সেবা পরিবর্তন',
        ],
        'close_conversation_button' => [
            'en' => 'Close conversation',
            'it' => 'Chiudi conversazione',
            'bn' => 'কথোপকথন শেষ করুন',
        ],

        // The 3 buttons WhatsApp's cap allows on every IN_SERVICE reply (see
        // MessagingChannel::sendTextWithQuickActions()). change_service/close_chat
        // were demoted to the typed-keyword hint below to make room for these.
        'rate_up_button' => [
            'en' => '👍 Helpful',
            'it' => '👍 Utile',
            'bn' => '👍 সহায়ক',
        ],
        'rate_down_button' => [
            'en' => '👎 Not helpful',
            'it' => '👎 Non utile',
            'bn' => '👎 সহায়ক নয়',
        ],
        // Meta caps button/quick-reply titles at 20 characters (WhatsApp interactive
        // buttons and Messenger/Instagram quick replies alike) — the Bengali label must
        // stay short enough to fit, unlike the longer phrasing used elsewhere for hints.
        'human_handover_button' => [
            'en' => '🙋 Talk to us',
            'it' => '🙋 Parla con noi',
            'bn' => '🙋 কথা বলুন',
        ],

        // One-line hint appended under every IN_SERVICE reply so switching service or
        // closing the chat still works by typing, even though they're no longer buttons.
        'nav_hint' => [
            'en' => 'Type "service" to change service, or "close" to end the chat.',
            'it' => 'Scrivi "cambia servizio" per cambiare servizio, o "chiudi" per terminare la chat.',
            'bn' => 'সেবা পরিবর্তন করতে "সেবা পরিবর্তন" লিখুন, অথবা কথোপকথন শেষ করতে "শেষ" লিখুন।',
        ],

        // Appended to the welcome message only for services with catalogue_enabled,
        // and used as the list/quick-reply prompt when "menu" is typed (see
        // HandleIncomingMessage::CATALOGUE_KEYWORDS).
        'view_catalogue_hint' => [
            'en' => 'Type "menu" any time to browse our full list.',
            'it' => 'Scrivi "menu" in qualsiasi momento per vedere la lista completa.',
            'bn' => 'সম্পূর্ণ তালিকা দেখতে যেকোনো সময় "মেনু" লিখুন।',
        ],
        'catalogue_prompt' => [
            'en' => 'Here\'s what we offer — tap an item for details:',
            'it' => 'Ecco cosa offriamo: tocca un elemento per i dettagli:',
            'bn' => 'আমরা যা অফার করি: বিস্তারিত জানতে একটি আইটেমে ট্যাপ করুন:',
        ],
        'catalogue_empty' => [
            'en' => 'Nothing is listed yet — ask us directly and we\'ll help.',
            'it' => 'Non c\'è ancora nulla elencato: chiedici direttamente e ti aiuteremo.',
            'bn' => 'এখনো কিছু তালিকাভুক্ত করা হয়নি—সরাসরি আমাদের জিজ্ঞাসা করুন, আমরা সাহায্য করব।',
        ],
        // Shown when a tapped catalogue item was unpublished/removed since the list
        // was sent — a stale button id must fail safely, not crash the agent call.
        'catalogue_entry_unavailable' => [
            'en' => 'Sorry, that item is no longer available. Type "menu" to see the current list.',
            'it' => 'Spiacenti, questo elemento non è più disponibile. Scrivi "menu" per la lista aggiornata.',
            'bn' => 'দুঃখিত, এই আইটেমটি আর উপলব্ধ নেই। বর্তমান তালিকা দেখতে "মেনু" লিখুন।',
        ],
    ],

    'services' => [

        'ticket' => [
            'label'        => ['en' => 'Ticket booking', 'it' => 'Biglietti', 'bn' => 'টিকিট বুকিং'],
            'prompt_label' => 'booking a travel ticket',
            'tool' => [
                'name'        => 'submit_ticket_request',
                'description' => 'Save a completed ticket booking request once all required details are collected and the customer has confirmed.',
                'fields' => [
                    'full_name'   => ['type' => 'string',  'required' => true,  'description' => "Customer's full name"],
                    'route'       => ['type' => 'string',  'required' => true,  'description' => 'From and to, e.g. Bologna to Rome'],
                    'travel_date' => ['type' => 'string',  'required' => true,  'description' => 'Date of travel, format YYYY-MM-DD'],
                    'passengers'  => ['type' => 'integer', 'required' => true,  'description' => 'Number of passengers'],
                    'notes'       => ['type' => 'string',  'required' => false, 'description' => 'Any extra preferences'],
                ],
            ],
        ],

        'license' => [
            'label'        => ['en' => 'Driving license', 'it' => 'Patente', 'bn' => 'ড্রাইভিং লাইসেন্স'],
            'prompt_label' => 'a driving license enquiry',
            'tool' => [
                'name'        => 'submit_license_request',
                'description' => 'Save a completed driving license enquiry once all required details are collected and confirmed.',
                'fields' => [
                    'full_name'    => ['type' => 'string', 'required' => true,  'description' => "Customer's full name"],
                    'request_type' => ['type' => 'string', 'required' => true,  'description' => 'New, renewal, or foreign conversion'],
                    'nationality'  => ['type' => 'string', 'required' => true,  'description' => 'Customer nationality'],
                    'phone'        => ['type' => 'string', 'required' => false, 'description' => 'Alternate contact number'],
                ],
            ],
        ],

        'immigration' => [
            'label'        => ['en' => 'Immigration', 'it' => 'Immigrazione', 'bn' => 'ইমিগ্রেশন'],
            'prompt_label' => 'an immigration enquiry',
            'tool' => [
                'name'        => 'submit_immigration_request',
                'description' => 'Save a completed immigration enquiry once all required details are collected and confirmed.',
                'fields' => [
                    'full_name'      => ['type' => 'string', 'required' => true,  'description' => "Customer's full name"],
                    'enquiry_type'   => ['type' => 'string', 'required' => true,  'description' => 'e.g. work permit, family reunification, citizenship'],
                    'nationality'    => ['type' => 'string', 'required' => true,  'description' => 'Customer nationality'],
                    'current_status' => ['type' => 'string', 'required' => false, 'description' => 'Current visa or residence status'],
                ],
            ],
        ],

    ],
];
