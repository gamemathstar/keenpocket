<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | Automated disbursement of a collected Adashi pot to the cycle's receiver.
    | Ships OFF — this moves real money OUT. While false, cycles close exactly as
    | today (status flip + notification) with no transfer attempted.
    |
    */
    'enabled' => env('PAYOUTS_ENABLED', false),

    // Provider: log | paystack | flutterwave. Defaults to the collection provider.
    'provider' => env('PAYOUTS_PROVIDER', env('PAYMENTS_PROVIDER', 'log')),

    'currency' => env('PAYMENTS_CURRENCY', 'NGN'),

    // Reuses the collection provider credentials (same Paystack/Flutterwave account).
    'providers' => [
        'paystack' => [
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        ],
        'flutterwave' => [
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
            'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com'),
        ],
    ],
];
