<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS channel
    |--------------------------------------------------------------------------
    |
    | Used for payment reminders (and any future critical alerts) as a second
    | channel alongside push. SMS costs money, so it ships OFF — reminders still
    | go out via push regardless. Shares provider credentials with OTP so one set
    | of keys powers both.
    |
    */
    'enabled' => env('SMS_ENABLED', false),

    // Provider: log | termii | africastalking | twilio
    'provider' => env('SMS_PROVIDER', env('OTP_PROVIDER', 'log')),

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
