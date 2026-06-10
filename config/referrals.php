<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Referral tracking
    |--------------------------------------------------------------------------
    |
    | Generates referral codes, attributes new signups, and exposes shareable
    | invite / WhatsApp links. Ships ON because it moves no money and is the
    | growth loop itself. Turn off to hide the endpoints entirely.
    |
    */
    'enabled' => env('REFERRALS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Rewards
    |--------------------------------------------------------------------------
    |
    | When a referral "qualifies" (the invitee completes `qualify_on`), a reward
    | record is created for both parties. Ships OFF: nothing of value is granted
    | until you decide the reward and wire it to a wallet/credit. Tracking and
    | invite links work regardless.
    |
    */
    'reward_enabled' => env('REFERRAL_REWARD_ENABLED', false),
    'reward_amount' => (int) env('REFERRAL_REWARD_AMOUNT', 0),

    // What counts as a qualifying action for the invited user: "register" | "join".
    // "join" = the invitee joins their first pocket or adashi (stronger signal).
    'qualify_on' => env('REFERRAL_QUALIFY_ON', 'join'),

    'code_length' => (int) env('REFERRAL_CODE_LENGTH', 7),

    /*
    |--------------------------------------------------------------------------
    | Invite links
    |--------------------------------------------------------------------------
    |
    | Base URL the invite deep link points at (app universal link / web). The
    | referral code is appended as `?ref=CODE`.
    |
    */
    'invite_base_url' => env('REFERRAL_INVITE_BASE_URL', env('APP_URL').'/invite'),

    // {link} is replaced with the invite URL when building the WhatsApp message.
    'whatsapp_message' => env(
        'REFERRAL_WHATSAPP_MESSAGE',
        'Join me on KeenPocket to save together — pockets & adashi made easy. {link}'
    ),
];
