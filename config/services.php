<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS (SmartWaste) — IPROG SMS API
    |--------------------------------------------------------------------------
    | Used by App\Services\SmsService for password-reset PINs and pickup-day
    | notices (port of utils/sms.js). Docs: https://sms.iprogtech.com/api/v1/documentation
    */
    'sms' => [
        'api_key' => env('IPROG_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI (SmartWaste) — Groq
    |--------------------------------------------------------------------------
    | Used by App\Services\AiInsightService for the analytics "AI Insights"
    | panel and per-report triage suggestions. Docs: https://console.groq.com/docs/openai
    */
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

];
