<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp channel (Meta Cloud API)
    |--------------------------------------------------------------------------
    |
    | Template messages for reminders — WhatsApp is the dominant channel in
    | Nigeria. Ships OFF; reminders still send via push/SMS. WhatsApp business
    | messaging requires pre-approved templates (referenced by name below).
    |
    */
    'enabled' => env('WHATSAPP_ENABLED', false),

    // Provider: log (dev) | meta
    'provider' => env('WHATSAPP_PROVIDER', 'log'),

    'providers' => [
        'meta' => [
            'token' => env('WHATSAPP_TOKEN'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com/v19.0'),
            'lang' => env('WHATSAPP_TEMPLATE_LANG', 'en'),
        ],
    ],

    // Map logical keys → approved template names in your WhatsApp Business account.
    'templates' => [
        'payment_reminder' => env('WHATSAPP_TEMPLATE_PAYMENT_REMINDER', 'payment_reminder'),
    ],
];
