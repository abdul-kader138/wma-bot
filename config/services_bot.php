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
