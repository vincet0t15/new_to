<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded account credentials
    |--------------------------------------------------------------------------
    |
    | Passwords applied by the demo and key-official seeders when an account is
    | first created. Re-seeding never resets an existing password. Production
    | installs must set both values explicitly; local and test installs fall
    | back to the historical development password so existing workflows keep
    | working.
    |
    */

    'demo_password' => env('DEMO_SEED_PASSWORD'),

    'key_official_password' => env('KEY_OFFICIAL_SEED_PASSWORD'),

    /*
     * Whether newly seeded accounts must change their password at first login.
     * Defaults off locally and in tests, on everywhere else. Set explicitly to
     * true or false to override the environment default.
     */
    'force_password_change' => env('SEED_FORCE_PASSWORD_CHANGE'),

];
