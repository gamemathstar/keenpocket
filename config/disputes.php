<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dispute resolution
    |--------------------------------------------------------------------------
    | Members can raise a dispute on a pocket or adashi; the admin reviews and
    | resolves or dismisses it. Trust feature, no money rail — defaults on.
    */
    'enabled' => env('DISPUTES_ENABLED', true),
];
