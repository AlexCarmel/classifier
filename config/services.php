<?php

return [

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'classify_enabled' => env('OPENAI_CLASSIFY_ENABLED', false),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 200),
        'temperature' => env('OPENAI_TEMPERATURE', 0.3),
        'rate_limit' => [
            'max_calls' => env('OPENAI_RATE_LIMIT_MAX_CALLS', 10),
            'window_seconds' => env('OPENAI_RATE_LIMIT_WINDOW_SECONDS', 60),
            'cache_key' => env('OPENAI_RATE_LIMIT_CACHE_KEY', 'openai_classify_rate_limit'),
        ],
    ],

];
