<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Administrator Bootstrap Account
    |--------------------------------------------------------------------------
    |
    | These values are used by the SuperAdminSeeder to create the one account
    | an installation cannot bootstrap without. The password is only applied
    | when the account is first created, so re-seeding never resets it.
    |
    */

    'name' => env('SUPERADMIN_NAME', 'Super Administrator'),

    'username' => env('SUPERADMIN_USERNAME', 'superadmin'),

    'email' => env('SUPERADMIN_EMAIL', 'superadmin@example.com'),

    'password' => env('SUPERADMIN_PASSWORD'),

];
