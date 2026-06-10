<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When false (the default) the OTP endpoints respond but never send SMS,
    | and registration/login are NOT gated by phone verification — i.e. the
    | application behaves exactly as before. Flip OTP_ENABLED=true only once an
    | SMS provider below is configured.
    |
    */
    'enabled' => env('OTP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Delivery provider
    |--------------------------------------------------------------------------
    |
    | Supported: "log" (writes the code to the log — for local/dev),
    | "termii", "africastalking", "twilio".
    |
    */
    'provider' => env('OTP_PROVIDER', 'log'),

    'code_length' => (int) env('OTP_CODE_LENGTH', 6),
    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    'providers' => [
        'termii' => [
            'api_key' => env('TERMII_API_KEY'),
            'sender_id' => env('TERMII_SENDER_ID', 'KeenPocket'),
            'base_url' => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
        ],
        'africastalking' => [
            'username' => env('AT_USERNAME'),
            'api_key' => env('AT_API_KEY'),
        ],
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
    ],
];
