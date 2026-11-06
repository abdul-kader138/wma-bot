<?php

return [

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'scopes' => [
            'openid',
            'email',
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/calendar.events.readonly',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
        ],
        'write_scopes' => [
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/calendar.events',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
    ],

    'messenger' => [
        'verify_token' => env('MESSENGER_VERIFY_TOKEN'),
        'app_secret' => env('MESSENGER_APP_SECRET'),
    ],

    'instagram' => [
        'verify_token' => env('INSTAGRAM_VERIFY_TOKEN'),
        'app_secret' => env('INSTAGRAM_APP_SECRET'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    ],

    // Deliberately separate from the chat LLM above: the embedding model only
    // feeds vector search (KnowledgeRetriever), so switching the answering
    // model (Anthropic/OpenAI/Mistral/...) never has to touch this.
    'embeddings' => [
        'driver' => env('EMBEDDINGS_PROVIDER', 'voyage'),

        'voyage' => [
            'key' => env('VOYAGE_API_KEY'),
            'model' => env('VOYAGE_EMBEDDING_MODEL', 'voyage-3.5-lite'),
            // Voyage models default to 1024 dims but support Matryoshka truncation via
            // `output_dimension` — pinned explicitly so the stored vector width never
            // silently changes if Voyage changes a model's default.
            'dimensions' => env('VOYAGE_EMBEDDING_DIMENSIONS', 512),
        ],
    ],

];
