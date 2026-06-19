<?php

return [

    'nav' => [
        'dashboard'        => 'Dashboard',
        'services'         => 'Services',
        'service_requests' => 'Service Requests',
        'conversations'    => 'Conversations',
        'faqs'             => 'FAQs',
        'users'            => 'Users',
        'roles'            => 'Roles',
        'system_settings'  => 'System Settings',
        'groups' => [
            'administration' => 'Administration',
        ],
    ],

    'stats' => [
        'new_requests'        => 'New Requests',
        'awaiting_staff'      => 'Awaiting staff action',
        'in_progress'         => 'In Progress',
        'being_handled'       => 'Being handled',
        'completed'           => 'Completed',
        'all_time'            => 'All time',
        'active_conversations'=> 'Active Conversations',
        'open_chats'          => 'Currently open chats',
    ],

    'service' => [
        'label'        => 'Service',
        'label_plural' => 'Services',
        'tabs' => [
            'basic'  => 'Basic Info',
            'bot'    => 'Bot Settings',
            'fields' => 'Data Fields',
        ],
        'sections' => [
            'identity'   => 'Identity',
            'labels'     => 'Multilingual Labels',
            'bot_config' => 'Bot Configuration',
            'fields'     => 'Data Fields',
        ],
        'fields' => [
            'label_en'        => 'Service Name',
            'color'           => 'Badge Color',
            'is_active'       => 'Active',
            'sort_order'      => 'Sort Order',
            'prompt_label'    => 'Prompt Label',
            'tool_name'       => 'Tool Function Name',
            'tool_description'=> 'Tool Description',
        ],
    ],

    'service_request' => [
        'label'        => 'Service Request',
        'label_plural' => 'Service Requests',
        'sections' => [
            'details' => 'Request Details',
        ],
        'fields' => [
            'phone'        => 'WhatsApp Phone',
            'service'      => 'Service',
            'status'       => 'Status',
            'payload'      => 'Collected Details',
            'staff_notes'  => 'Staff Notes',
            'phone_short'  => 'Phone',
            'received'     => 'Received',
            'last_updated' => 'Last Updated',
        ],
        'status' => [
            'new'         => 'New',
            'in_progress' => 'In Progress',
            'done'        => 'Done',
        ],
        'actions' => [
            'in_progress'  => 'In Progress',
            'done'         => 'Done',
            'mark_as_done' => 'Mark as Done',
        ],
    ],

    'conversation' => [
        'label'        => 'Conversation',
        'label_plural' => 'Conversations',
        'fields' => [
            'phone'         => 'Phone',
            'last_activity' => 'Last Activity',
        ],
        'steps' => [
            'NEW'           => 'New',
            'AWAIT_LANG'    => 'Awaiting Language',
            'AWAIT_SERVICE' => 'Awaiting Service',
            'IN_SERVICE'    => 'In Service',
            'DONE'          => 'Done',
        ],
        'actions' => [
            'reset' => 'Reset',
        ],
    ],

    'faq' => [
        'label'        => 'FAQ',
        'label_plural' => 'FAQs',
        'sections' => [
            'faq'    => 'FAQ',
            'answer' => 'Answer',
        ],
        'fields' => [
            'applies_to'   => 'Applies to',
            'all_services' => 'All services',
            'active'       => 'Active',
            'question'     => 'Reference question (for staff)',
            'keywords'     => 'Trigger phrases',
            'keywords_help'=> 'Words or short phrases that should trigger this answer, e.g. "price", "how much", "opening hours".',
            'triggers'     => 'Triggers',
        ],
    ],

    'user' => [
        'label'        => 'User',
        'label_plural' => 'Users',
        'sections' => [
            'account' => 'Account Details',
            'roles'   => 'Roles & Permissions',
        ],
        'fields' => [
            'name'             => 'Name',
            'email'            => 'Email',
            'password'         => 'Password',
            'confirm_password' => 'Confirm Password',
            'password_help'    => 'Leave blank to keep the current password (when editing).',
            'roles'            => 'Roles',
            'roles_help'       => 'Assign one or more roles. Roles control what this user can see and do in the admin panel.',
            'verified'         => 'Verified',
            'not_verified'     => 'Not verified',
            'role'             => 'Role',
        ],
    ],

    'settings' => [
        'title'   => 'System Settings',
        'save'    => 'Save Settings',
        'saved'   => 'Settings saved. Refresh the page to apply theme changes.',
        'tabs' => [
            'general'    => 'General',
            'appearance' => 'Appearance',
            'whatsapp'   => 'WhatsApp',
            'claude'     => 'Claude AI',
            'bot'        => 'Bot Behaviour',
            'email'      => 'Email',
        ],
        'sections' => [
            'application'  => 'Application',
            'color_theme'  => 'Color Theme',
            'panel_mode'   => 'Panel Mode',
            'auth_bg'      => 'Auth Page Background',
            'branding'     => 'Branding Assets',
            'wa_api'       => 'WhatsApp Business API',
            'claude_api'   => 'Anthropic Claude API',
            'response'     => 'Response Settings',
            'mail_sender'  => 'Mail Sender',
        ],
        'fields' => [
            'app_name'         => 'Application Name',
            'app_tagline'      => 'Tagline',
            'support_email'    => 'Support Email',
            'maintenance_mode'        => 'Maintenance Mode',
            'maintenance_help'        => 'When enabled, the admin panel will display a maintenance notice.',
            'default_language'        => 'Default Language',
            'default_language_help'   => 'The language shown on the login page and used as the panel default. Individual admins can still switch language using the topbar selector.',
        ],
    ],

    'dashboard' => [
        'greeting' => [
            'morning'   => 'Good Morning',
            'afternoon' => 'Good Afternoon',
            'evening'   => 'Good Evening',
        ],
        'quick' => [
            'pending' => 'Pending',
            'active'  => 'Active Chats',
            'today'   => 'Today',
        ],
        'chart' => [
            'heading'       => 'Service Requests',
            'last_7_days'   => 'Last 7 days',
            'last_14_days'  => 'Last 14 days',
            'last_30_days'  => 'Last 30 days',
            'dataset_label' => 'Requests',
        ],
        'recent' => [
            'heading' => 'Recent Requests',
        ],
    ],

    'profile' => [
        'sections' => [
            'picture'  => 'Profile Picture',
            'details'  => 'Personal Information',
            'security' => 'Change Password',
        ],
        'descriptions' => [
            'picture'  => 'Upload a square image. It will be displayed as a circle.',
            'details'  => 'Update your name and email address.',
            'security' => 'Leave blank to keep your current password.',
        ],
        'fields' => [
            'avatar' => 'Avatar',
        ],
    ],

    'brand' => [
        'headline_dark'  => 'Your :name Control Center.',
        'headline_light' => 'Manage your AI Bot with confidence.',
        'admin_badge'    => 'Admin',
        'footer'         => 'Powered by Claude AI & WhatsApp Business API',
        'features' => [
            'AI-powered WhatsApp conversations',
            'Smart FAQ matching & routing',
            'Real-time service request management',
            'Full conversation history & analytics',
        ],
    ],

];
