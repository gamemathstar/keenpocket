<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Charity donations (Sadaqah / fi-sabilillah)
    |--------------------------------------------------------------------------
    | Platform master switch. Charity is a savings feature (no money rail of its
    | own — donations are recorded like contributions), so it defaults ON.
    | Each pocket's charity is still opt-in: the admin enables it per pocket
    | (pockets.charity_enabled, default false), and donor identities/amounts are
    | private by default (pockets.charity_donors_visible, default false).
    */
    'enabled' => env('CHARITY_ENABLED', true),
];
