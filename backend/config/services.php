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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT'),
    ],

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'efi' => [
        'url'           => env('EFI_URL', 'https://pix.api.efipay.com.br'),
        'client_id'     => env('EFI_CLIENT_ID', ''),
        'client_secret' => env('EFI_CLIENT_SECRET', ''),
        'cert_path'     => env('EFI_CERT_PATH', ''),   // Caminho absoluto para o .p12 ou .pem
        'pix_key'       => env('EFI_PIX_KEY', ''),     // Chave PIX cadastrada na Efí
        'sandbox'       => env('EFI_SANDBOX', true),
    ],

    'oxapay' => [
        'merchant_key'   => env('OXAPAY_MERCHANT_KEY', ''),
        'webhook_secret' => env('OXAPAY_WEBHOOK_SECRET', ''),
        'base_url'       => 'https://api.oxapay.com',
    ],

    'wise' => [
        'api_key'        => env('WISE_API_KEY', ''),
        'profile_id'     => env('WISE_PROFILE_ID', ''),  // ID do perfil Business na Wise
        'account_email'  => env('WISE_ACCOUNT_EMAIL', ''),
        'webhook_secret' => env('WISE_WEBHOOK_SECRET', ''),
        'sandbox'        => env('WISE_SANDBOX', true),
    ],

];
