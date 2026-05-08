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

    'resend' => [
        'key' => env('RESEND_API_KEY', env('RESEND_KEY')),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'vertex_ai' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', env('GOOGLE_CLOUD_PROJECT')),
        'location' => env('GOOGLE_CLOUD_LOCATION', 'us-central1'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'credentials_json' => env('VERTEX_AI_SERVICE_ACCOUNT_JSON'),
        'model' => env('VERTEX_AI_MODEL', 'gemini-2.5-flash'),
        /** Bật function calling (tạo hợp đồng, tra cứu phòng/khách…) trên màn /chat */
        'enable_chat_tools' => env('VERTEX_AI_ENABLE_CHAT_TOOLS', true),
    ],

    'sepay' => [
        'api_key' => env('SEPAY_API_KEY'),
        'webhook_url' => env('APP_URL') . '/api/webhooks/sepay',
        'bank_name' => env('SEPAY_BANK_NAME', 'TPBank'),
        'account_number' => env('SEPAY_ACCOUNT_NUMBER', '46166378666'),
        'account_name' => env('SEPAY_ACCOUNT_NAME', 'TRAN DUC THANG'),
        'branch' => env('SEPAY_BRANCH', 'Chi nhánh Hà Nội'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

];
