<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gamification
    |--------------------------------------------------------------------------
    |
    | Streaks + achievement badges, computed on the fly from existing activity
    | (no new mutable state). Ships ON; engagement only, no money. Thresholds are
    | tunable here so badges can be retuned without code changes.
    |
    */
    'enabled' => env('GAMIFICATION_ENABLED', true),

    'thresholds' => [
        'reliable_payer' => ['min_reliability' => 90, 'min_invoices' => 3],
        'top_organizer' => ['min_rating' => 4.5, 'min_ratings' => 3],
        'recruiter' => ['min_referrals' => 3],
        'big_saver' => ['min_contributed' => 100000], // in naira
    ],
];
