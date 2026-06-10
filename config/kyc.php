<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | Identity verification (BVN / NIN) for members — the trust gate for letting
    | strangers transact. Ships OFF: while false, nothing is verified and no flow
    | requires KYC, so behaviour is unchanged. Turn on once a provider is keyed.
    |
    */
    'enabled' => env('KYC_ENABLED', false),

    // Provider: log (simulates success, dev) | dojah
    'provider' => env('KYC_PROVIDER', 'log'),

    // When true, the public directory only lists pockets from KYC-verified
    // organizers. Has no effect while KYC is disabled.
    'gate_directory' => env('KYC_GATE_DIRECTORY', true),

    'providers' => [
        'dojah' => [
            'app_id' => env('DOJAH_APP_ID'),
            'secret_key' => env('DOJAH_SECRET_KEY'),
            'base_url' => env('DOJAH_BASE_URL', 'https://api.dojah.io'),
        ],
    ],
];
