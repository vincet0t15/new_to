<?php

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * An account that may use the roles screen.
 */
function roleScreenAdmin(bool $superAdmin = false): User
{
    $user = User::factory()->create();
    $user->assignRole(
        Role::query()
            ->where('slug', $superAdmin
                ? SystemRole::SuperAdmin->value
                : SystemRole::Admin->value)
            ->sole(),
    );

    return $user;
}

/**
 * The seeded role behind a stable slug.
 */
function seededRole(SystemRole $role): Role
{
    return Role::query()->where('slug', $role->value)->sole();
}

test('guests are redirected to the login page from the roles screen', function () {
    $this->get(route('roles.index'))->assertRedirect(route('login'));
});

test('an account without administrator access cannot list roles', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('roles.index'))
        ->assertForbidden();
});

test('an administrator sees every built-in role and the permission list', function () {
    $this->actingAs(roleScreenAdmin())
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/index')
            ->has('roles.data', count(SystemRole::cases()))
            ->has('permissions')
            ->where('filters.search', '')
        );
});

test('roles can be searched by name', function () {
    Role::factory()->create(['name' => 'Records Officer', 'slug' => 'records_officer']);

    $this->actingAs(roleScreenAdmin())
        ->get(route('roles.index', ['search' => 'Records']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('roles.data', 1)
            ->where('roles.data.0.name', 'Records Officer')
            ->where('filters.search', 'Records')
        );
});

test('a custom role can be created with permissions', function () {
    $this->actingAs(roleScreenAdmin())
        ->post(route('roles.store'), [
            'name' => 'Records Officer',
            'description' => 'Keeps the records room in order.',
            'permissions' => ['offices.view', 'employees.view'],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $role = Role::query()->where('slug', 'records_officer')->sole();

    expect($role->is_system)->toBeFalse()
        ->and($role->description)->toBe('Keeps the records room in order.')
        ->and($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['employees.view', 'offices.view']);
});

test('a custom role can be renamed and its permissions replaced', function () {
    $role = Role::factory()->create(['name' => 'Records Officer', 'slug' => 'records_officer']);

    $this->actingAs(roleScreenAdmin())
        ->put(route('roles.update', $role), [
            'name' => 'Records Custodian',
            'description' => 'Renamed role.',
            'permissions' => ['offices.view'],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $role->refresh();

    expect($role->name)->toBe('Records Custodian')
        ->and($role->permissions->pluck('name')->all())->toBe(['offices.view']);
});

test('an administrator without super admin rights cannot change a built-in role', function () {
    $employeeRole = seededRole(SystemRole::Employee);
    $before = $employeeRole->permissions->pluck('name')->sort()->values()->all();

    $this->actingAs(roleScreenAdmin())
        ->put(route('roles.update', $employeeRole), [
            'name' => 'Renamed Employee',
            'description' => 'Should not take effect.',
            'permissions' => ['offices.view'],
        ])
        ->assertRedirect();

    $employeeRole->refresh();

    expect($employeeRole->name)->toBe('Employee')
        ->and($employeeRole->permissions->pluck('name')->sort()->values()->all())->toBe($before);
});

test('a super admin can change the permissions of a built-in role', function () {
    $employeeRole = seededRole(SystemRole::Employee);

    $this->actingAs(roleScreenAdmin(superAdmin: true))
        ->put(route('roles.update', $employeeRole), [
            'name' => 'Employee',
            'description' => 'Employee self-service.',
            'permissions' => ['offices.view', 'travel-orders.view'],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($employeeRole->refresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['offices.view', 'travel-orders.view']);
});

test('the super admin role cannot be stripped of its permissions', function () {
    $superAdminRole = seededRole(SystemRole::SuperAdmin);
    $before = $superAdminRole->permissions->count();

    $this->actingAs(roleScreenAdmin(superAdmin: true))
        ->put(route('roles.update', $superAdminRole), [
            'name' => 'Super Admin',
            'description' => 'Super Admin',
            'permissions' => ['offices.view'],
        ])
        ->assertRedirect();

    expect($superAdminRole->refresh()->permissions->count())->toBe($before);
});

test('built-in roles cannot be deleted', function () {
    $employeeRole = seededRole(SystemRole::Employee);

    $this->actingAs(roleScreenAdmin())
        ->delete(route('roles.destroy', $employeeRole))
        ->assertRedirect();

    expect(Role::query()->whereKey($employeeRole->id)->exists())->toBeTrue();
});

test('a custom role in use cannot be deleted', function () {
    $role = Role::factory()->create();
    User::factory()->create()->assignRole($role);

    $this->actingAs(roleScreenAdmin())
        ->delete(route('roles.destroy', $role))
        ->assertRedirect();

    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue();
});

test('an unused custom role can be deleted', function () {
    $role = Role::factory()->create();

    $this->actingAs(roleScreenAdmin())
        ->delete(route('roles.destroy', $role))
        ->assertRedirect();

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse();
});
