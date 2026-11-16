<?php

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often want to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Grant an account entry to the admin portal without granting anything else.
 *
 * Portal access is role-based, while the account's actual capabilities still
 * come from the explicit permissions a test attaches. Creating the role
 * without permissions keeps permission-gating tests honest.
 */
function grantAdminPortal(User $user): User
{
    $role = Role::firstOrCreate(
        ['guard_name' => 'web', 'slug' => SystemRole::Admin->value],
        [
            'name' => 'Admin',
            'description' => 'Admin',
            'is_system' => true,
        ],
    );

    $user->assignRole($role);

    return $user;
}
