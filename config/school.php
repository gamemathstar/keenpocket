<?php

return [
    /*
    |--------------------------------------------------------------------------
    | School fee-management module
    |--------------------------------------------------------------------------
    | Schools are created only by users the super admin has enrolled. Whether it
    | is a paid service is a super-admin decision (we never hold money — "paid"
    | just means the super admin grants access after settling offline).
    */
    'enabled' => env('SCHOOL_ENABLED', true),
    'paid' => env('SCHOOL_PAID', false),

    // Emails that are always treated as super admins (in addition to the
    // users.is_super_admin flag). Set SCHOOL_SUPER_ADMINS="a@x.com,b@y.com".
    'super_admins' => array_filter(explode(',', env('SCHOOL_SUPER_ADMINS', 'aiabubakar3@gmail.com'))),
];
