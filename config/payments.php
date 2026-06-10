<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When false (the default) the payment endpoints respond but never contact a
    | gateway, and the existing manual "mark as Paid" flow is completely
    | unchanged. Flip PAYMENTS_ENABLED=true only once a provider below is keyed.
    |
    */
    'enabled' => env('PAYMENTS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Gateway
    |--------------------------------------------------------------------------
    |
    | Supported: "log" (no network call — simulates success, for local/dev),
    | "paystack", "flutterwave".
    |
    */
    'provider' => env('PAYMENTS_PROVIDER', 'log'),

    'currency' => env('PAYMENTS_CURRENCY', 'NGN'),

    // Where the gateway redirects the user back to after payment (frontend/app).
    'callback_url' => env('PAYMENTS_CALLBACK_URL', env('APP_URL').'/payments/callback'),

    'providers' => [
        'paystack' => [
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        ],
        'flutterwave' => [
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            // Set in the Flutterwave dashboard; sent back as the `verif-hash` header.
            'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
            'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com'),
        ],
    ],
];
