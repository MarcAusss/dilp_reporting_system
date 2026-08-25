<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Super Admin
    |--------------------------------------------------------------------------
    |
    | Used only to bootstrap the first system administrator account.
    |
    */

    'super_admin' => [
        'name' => env(
            'DILP_SUPER_ADMIN_NAME',
            'System Super Administrator'
        ),

        'email' => env('DILP_SUPER_ADMIN_EMAIL'),

        'password' => env('DILP_SUPER_ADMIN_PASSWORD'),
    ],

];