<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'payments' => [
        'driver' => env('PAYMENT_DRIVER', 'demo'), // 'demo' = fake checkout (no real money), 'paypal' or 'hitpay' = real gateways
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_CLIENT_SECRET'),
        'base' => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'), // sandbox = fake money. Live: https://api-m.paypal.com
        'currency' => env('PAYPAL_CURRENCY', 'PHP'),                          // use USD if the sandbox refuses PHP
        'return_base' => env('PAYPAL_RETURN_BASE'),                           // optional: public URL of the backend (e.g. an ngrok link)
    ],

    'hitpay' => [
        'key' => env('HITPAY_API_KEY'),                                        // from HitPay dashboard > API Keys
        'salt' => env('HITPAY_SALT'),                                          // same page; only for the production webhook
        'base' => env('HITPAY_BASE_URL', 'https://api.sandbox.hit-pay.com/v1'), // sandbox = fake money. Live: https://api.hit-pay.com/v1
        'currency' => env('HITPAY_CURRENCY', 'PHP'),
        'methods' => array_filter(array_map('trim', explode(',', env('HITPAY_METHODS', '')))), // empty = all methods enabled on your account
        'redirect' => env('HITPAY_REDIRECT_URL'),                              // optional override of the "thank you" page
    ],

    'semaphore' => [
        'key' => env('SEMAPHORE_API_KEY'),
        'sender_name' => env('SEMAPHORE_SENDER_NAME'),
        'endpoint' => env('SEMAPHORE_API_ENDPOINT', 'https://api.semaphore.co/api/v4/messages'),
    ],

];
