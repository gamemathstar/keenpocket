<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public directory
    |--------------------------------------------------------------------------
    |
    | Lets logged-in users browse and request to join "open" pockets (those not
    | set to invitation-only, with slots still available). Discovery turns a
    | closed tool into a marketplace. Ships ON; no money is involved.
    |
    */
    'enabled' => env('DISCOVERY_ENABLED', true),

    // Max rows per directory page.
    'per_page' => (int) env('DISCOVERY_PER_PAGE', 20),
];
