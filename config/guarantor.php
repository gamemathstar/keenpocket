<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Guarantor-backed join requests
    |--------------------------------------------------------------------------
    | Platform switch for the guarantor feature. It is a trust feature (no money
    | rail), so it defaults on. Each pocket still opts in per-pocket via
    | pockets.guarantor_required (default false): when on, a join request must be
    | recommended by a guarantor before the admin can accept it.
    */
    'enabled' => env('GUARANTOR_ENABLED', true),
];
