<?php

return [

    /*
    |--------------------------------------------------------------------------
    | In-app wallet
    |--------------------------------------------------------------------------
    |
    | An internal balance members fund once and spend on contributions, so they
    | don't re-enter card details every cycle. Ships OFF (it is money handling);
    | while disabled the endpoints are inert and no balances move.
    |
    | Funding a real balance requires online payments (config/payments.php). With
    | the `log` payment provider, top-ups are credited immediately for dev.
    |
    */
    'enabled' => env('WALLET_ENABLED', false),

    'currency' => env('PAYMENTS_CURRENCY', 'NGN'),
];
