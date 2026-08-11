<?php

return [

    'auth' => [
        'two_factor' => [
            'heading'       => 'Two-factor authentication',
            'code_label'    => 'Authentication code',
            'code_helper'   => 'Enter the 6-digit code from your authenticator app, or one of your recovery codes.',
            'back_to_login' => 'Back to login',
        ],
    ],

    'nav' => [
        'dashboard'        => 'Dashboard',
        'services'         => 'Services',
        'service_requests' => 'Service Requests',
        'conversations'    => 'Conversations',
        'faqs'             => 'FAQs',
        'documents'        => 'Documents',
        'audit_logs'       => 'Audit Logs',
        'users'            => 'Users',
        'roles'            => 'Roles',
        'system_settings'  => 'System Settings',
        'whatsapp_accounts' => 'Messaging Accounts',
        'groups' => [
            'administration' => 'Administration',
        ],
    ],

    'audit_log' => [
        'label' => 'Audit Log', 'label_plural' => 'Audit Logs',
        'fields' => ['time' => 'Time', 'actor' => 'Actor', 'owner' => 'Document Owner', 'path' => 'Path', 'details' => 'Details'],
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
            'phone'        => 'Phone / Contact ID',
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
        'sections' => [
            'details'    => 'Details',
            'transcript' => 'Conversation Transcript',
        ],
        'fields' => [
            'phone'         => 'Phone',
            'last_activity' => 'Last Activity',
            'started_at'    => 'Started',
            'language'      => 'Language',
            'role'          => 'From',
            'message'       => 'Message',
        ],
        'roles' => [
            'user'      => 'Customer',
            'assistant' => 'Bot',
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

    'whatsapp_account' => [
        'label'        => 'Messaging Account',
        'label_plural' => 'Messaging Accounts',
        'sections' => [
            'identity'    => 'Identity',
            'credentials' => 'Meta API Credentials',
        ],
        'fields' => [
            'name'             => 'Name',
            'name_help'        => 'A friendly label to identify this number, e.g. "Sales" or "Support".',
            'phone_number_id'  => 'Phone Number ID',
            'phone_number_id_help' => 'Found in Meta Business Suite → WhatsApp → API Setup.',
            'waba_id'          => 'WhatsApp Business Account ID',
            'access_token'     => 'Access Token',
            'access_token_help'=> 'Permanent or temporary access token from Meta for this number.',
            'api_version'      => 'API Version',
            'is_active'        => 'Active',
            'is_active_help'   => 'Inactive accounts will not receive or send WhatsApp messages.',
            'is_default'       => 'Default',
            'is_default_help'  => 'Used as a fallback when a number cannot be otherwise identified.',
        ],
    ],

    'settings' => [
        'title'   => 'System Settings',
        'save'    => 'Save Settings',
        'saved'   => 'Settings saved. Refresh the page to apply theme changes.',
        'tabs' => [
            'general'    => 'General',
            'appearance' => 'Appearance',
            'security'   => 'Security',
            'whatsapp'   => 'Messaging Channels',
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
            'two_factor'   => 'Two-Factor Authentication',
            'wa_api'       => 'WhatsApp Business API',
            'messenger_api' => 'Facebook Messenger',
            'instagram_api' => 'Instagram',
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
            'two_factor_enabled'      => 'Enable Two-Factor Authentication',
            'two_factor_enabled_help' => 'Turns the two-factor authentication feature on or off for the entire admin panel. When off, no one is challenged for a code at login, and admins cannot enable it on their profile — existing setups are kept but inactive until this is switched back on.',
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
        'conversations_chart' => [
            'heading'       => 'Conversations',
            'last_7_days'   => 'Last 7 days',
            'last_14_days'  => 'Last 14 days',
            'last_30_days'  => 'Last 30 days',
            'dataset_label' => 'Conversations',
        ],
        'recent' => [
            'heading' => 'Recent Requests',
        ],
    ],

    'profile' => [
        'sections' => [
            'picture'    => 'Profile Picture',
            'details'    => 'Personal Information',
            'security'   => 'Change Password',
            'two_factor' => 'Two-Factor Authentication',
        ],
        'descriptions' => [
            'picture'    => 'Upload a square image. It will be displayed as a circle.',
            'details'    => 'Update your name and email address.',
            'security'   => 'Leave blank to keep your current password.',
            'two_factor' => 'Add an extra layer of security to your account using an authenticator app.',
        ],
        'fields' => [
            'avatar' => 'Avatar',
        ],
        'two_factor' => [
            'enabled'                  => 'Two-factor authentication is enabled.',
            'disabled'                 => 'Two-factor authentication is not enabled.',
            'disabled_globally'        => 'Two-factor authentication has been turned off for this application by an administrator.',
            'enable_action'            => 'Enable',
            'disable_action'           => 'Disable',
            'disable_confirm_heading'  => 'Disable two-factor authentication?',
            'disable_confirm_body'     => 'You will no longer be asked for a code when signing in.',
            'show_recovery_codes'      => 'Show recovery codes',
            'regenerate_recovery_codes' => 'Regenerate recovery codes',
            'setup_heading'            => 'Scan the QR code',
            'setup_description'        => 'Scan this QR code with your authenticator app (Google Authenticator, Authy, 1Password, etc.), then enter the 6-digit code it generates to confirm.',
            'secret_label'             => 'Or enter this code manually',
            'code_label'               => 'Confirmation code',
            'code_helper'              => 'Enter the 6-digit code from your authenticator app.',
            'invalid_code'             => 'The provided code was invalid.',
            'confirm_action'           => 'Confirm',
            'recovery_codes_heading'   => 'Recovery codes',
            'recovery_codes_description' => 'Store these codes in a secure place. Each one can be used once to sign in if you lose access to your authenticator app.',
            'enabled_notification'    => 'Two-factor authentication enabled.',
            'disabled_notification'   => 'Two-factor authentication disabled.',
            'regenerated_notification' => 'Recovery codes regenerated.',
        ],
    ],

    'brand' => [
        'headline_dark'  => 'Your :name Control Center.',
        'headline_light' => 'Manage your AI Bot with confidence.',
        'admin_badge'    => 'Admin',
        'footer'         => 'Powered by Claude AI & Meta Messaging APIs',
        'features' => [
            'AI-powered WhatsApp, Messenger & Instagram conversations',
            'Smart FAQ matching & routing',
            'Real-time service request management',
            'Full conversation history & analytics',
        ],
    ],

];
