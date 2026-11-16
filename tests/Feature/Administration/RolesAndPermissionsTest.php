<?php

use App\Enums\SystemRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Fetch a seeded system role by its stable slug.
 */
function systemRole(SystemRole $role): Role
{
    return Role::query()
        ->where('guard_name', 'web')
        ->where('slug', $role->value)
        ->firstOrFail();
}

test('the seeder creates every system role', function () {
    $slugs = Role::query()->pluck('slug');

    foreach (SystemRole::cases() as $role) {
        expect($slugs)->toContain($role->value);
    }
});

test('the super administrator holds every permission', function () {
    $user = User::factory()->create();
    $user->assignRole(systemRole(SystemRole::SuperAdmin));

    expect($user->getAllPermissions())->toHaveCount(
        Permission::query()->count()
    );
});

test('the administrator cannot manage admins', function () {
    $user = User::factory()->create();
    $user->assignRole(systemRole(SystemRole::Admin));

    expect($user->can('administration.view'))->toBeTrue()
        ->and($user->can('accounts.manage-admins'))->toBeFalse();
});

test('an employee cannot enter the administration portal', function () {
    $user = User::factory()->create();
    $user->assignRole(systemRole(SystemRole::Employee));

    expect($user->can('administration.view'))->toBeFalse();
});

test('a multi-role account resolves the most privileged role slug', function () {
    $user = User::factory()->create();
    $user->assignRole([
        systemRole(SystemRole::Employee),
        systemRole(SystemRole::DepartmentHead),
        systemRole(SystemRole::Admin),
    ]);

    expect($user->primaryRoleSlug())->toBe(SystemRole::Admin->value)
        ->and($user->isAdministrator())->toBeTrue()
        ->and($user->isSuperAdministrator())->toBeFalse();
});

test('shared auth props expose role slugs and permissions', function () {
    $user = User::factory()->create();
    $user->assignRole(systemRole(SystemRole::Admin));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.roles.0', SystemRole::Admin->value)
            ->where('auth.permissions', fn ($permissions): bool => collect($permissions)->contains('administration.view'))
        );
});

test('the super administrator seeder creates the configured account', function () {
    $this->seed(SuperAdminSeeder::class);

    $user = User::query()
        ->where('username', config('superadmin.username'))
        ->sole();

    expect($user->name)->toBe(config('superadmin.name'))
        ->and($user->email)->toBe(config('superadmin.email'))
        ->and($user->hasRoleSlug(SystemRole::SuperAdmin->value))->toBeTrue()
        ->and($user->roles)->toHaveCount(1)
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('re-seeding the super administrator keeps the existing password', function () {
    $this->seed(SuperAdminSeeder::class);

    $user = User::query()
        ->where('username', config('superadmin.username'))
        ->sole();

    $user->update(['password' => 'a-changed-password']);

    $this->seed(SuperAdminSeeder::class);

    expect(Hash::check('a-changed-password', $user->refresh()->password))->toBeTrue();
});
