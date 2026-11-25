<?php

use App\Enums\SystemRole;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * An account that may use the accounts screen.
 */
function accountScreenAdmin(bool $superAdmin = false): User
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
function accountRole(SystemRole $role): Role
{
    return Role::query()->where('slug', $role->value)->sole();
}

test('guests are redirected to the login page from the accounts screen', function () {
    $this->get(route('accounts.index'))->assertRedirect(route('login'));
});

test('an account without administrator access cannot list accounts', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('accounts.index'))
        ->assertForbidden();
});

test('an administrator sees the accounts and the role choices', function () {
    User::factory()->create(['name' => 'Maria Santos', 'username' => 'msantos']);

    $this->actingAs(accountScreenAdmin())
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('accounts/index')
            ->has('accounts.data', 2)
            ->has('roles', count(SystemRole::cases()))
            ->where('filters.search', '')
        );
});

test('accounts can be searched by name, username, or email', function () {
    User::factory()->create([
        'name' => 'Maria Santos',
        'username' => 'msantos',
        'email' => 'maria@example.com',
    ]);
    User::factory()->create(['name' => 'Pedro Reyes', 'username' => 'preyes']);

    $this->actingAs(accountScreenAdmin())
        ->get(route('accounts.index', ['search' => 'msantos']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accounts.data', 1)
            ->where('accounts.data.0.username', 'msantos')
        );
});

test('a super admin can create an account and link it to an employee', function () {
    $employee = Employee::factory()->create();
    $employeeRole = accountRole(SystemRole::Employee);

    $this->actingAs(accountScreenAdmin(superAdmin: true))
        ->post(route('accounts.store'), [
            'name' => 'Juan Dela Cruz',
            'username' => 'JDELACRUZ',
            'email' => 'juan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'employee_id' => $employee->id,
            'roles' => [$employeeRole->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $account = User::query()->where('username', 'jdelacruz')->sole();

    expect($account->name)->toBe('Juan Dela Cruz')
        ->and($account->hasRoleSlug(SystemRole::Employee->value))->toBeTrue()
        ->and($account->employee_id)->toBe($employee->id)
        ->and($account->is_active)->toBeTrue()
        ->and(Hash::check('secret-password', $account->password))->toBeTrue();
});

test('an administrator can create an inactive account', function () {
    $this->actingAs(accountScreenAdmin())
        ->post(route('accounts.store'), [
            'name' => 'Inactive User',
            'username' => 'inactiveuser',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'is_active' => false,
            'roles' => [accountRole(SystemRole::Employee)->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::query()->where('username', 'inactiveuser')->sole()->is_active)->toBeFalse();
});

test('an administrator can create a non-administrator account', function () {
    $this->actingAs(accountScreenAdmin())
        ->post(route('accounts.store'), [
            'name' => 'Ana Lopez',
            'username' => 'alopez',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'roles' => [
                accountRole(SystemRole::Employee)->id,
                accountRole(SystemRole::DepartmentHead)->id,
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $account = User::query()->where('username', 'alopez')->sole();

    expect($account->hasRoleSlug(SystemRole::Employee->value))->toBeTrue()
        ->and($account->hasRoleSlug(SystemRole::DepartmentHead->value))->toBeTrue();
});

test('an administrator cannot create an account holding an administrator role', function () {
    $this->actingAs(accountScreenAdmin())
        ->post(route('accounts.store'), [
            'name' => 'Ana Lopez',
            'username' => 'alopez',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'roles' => [accountRole(SystemRole::Admin)->id],
        ])
        ->assertSessionHasErrors('roles');

    expect(User::query()->where('username', 'alopez')->exists())->toBeFalse();
});

test('an employee can only be linked to one account', function () {
    $employee = Employee::factory()->create();
    $linked = User::factory()->create();
    $linked->employee()->associate($employee);
    $linked->save();

    $this->actingAs(accountScreenAdmin())
        ->post(route('accounts.store'), [
            'name' => 'Ana Lopez',
            'username' => 'alopez',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'employee_id' => $employee->id,
            'roles' => [accountRole(SystemRole::Employee)->id],
        ])
        ->assertSessionHasErrors('employee_id');
});

test("an account's roles can be replaced", function () {
    $target = User::factory()->create();
    $target->assignRole(accountRole(SystemRole::Employee));

    $this->actingAs(accountScreenAdmin(superAdmin: true))
        ->put(route('accounts.roles.update', $target), [
            'roles' => [accountRole(SystemRole::DepartmentHead)->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($target->refresh()->hasRoleSlug(SystemRole::DepartmentHead->value))->toBeTrue()
        ->and($target->hasRoleSlug(SystemRole::Employee->value))->toBeFalse();
});

test('an administrator cannot change their own roles', function () {
    $actor = accountScreenAdmin();

    $this->actingAs($actor)
        ->put(route('accounts.roles.update', $actor), [
            'roles' => [accountRole(SystemRole::Employee)->id],
        ])
        ->assertRedirect();

    expect($actor->refresh()->hasRoleSlug(SystemRole::Admin->value))->toBeTrue();
});

test('an administrator cannot grant an administrator role', function () {
    $target = User::factory()->create();
    $target->assignRole(accountRole(SystemRole::Employee));

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.roles.update', $target), [
            'roles' => [accountRole(SystemRole::Admin)->id],
        ])
        ->assertSessionHasErrors('roles');

    expect($target->refresh()->hasRoleSlug(SystemRole::Employee->value))->toBeTrue()
        ->and($target->hasRoleSlug(SystemRole::Admin->value))->toBeFalse();
});

test('an administrator cannot strip another administrator', function () {
    $target = User::factory()->create();
    $target->assignRole(accountRole(SystemRole::Admin));

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.roles.update', $target), [
            'roles' => [accountRole(SystemRole::Employee)->id],
        ])
        ->assertSessionHasErrors('roles');

    expect($target->refresh()->hasRoleSlug(SystemRole::Admin->value))->toBeTrue();
});

test("an account's active status can be toggled", function () {
    $target = User::factory()->create();

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.status.update', $target), ['is_active' => false])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($target->refresh()->is_active)->toBeFalse();

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.status.update', $target), ['is_active' => true])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($target->refresh()->is_active)->toBeTrue();
});

test('an administrator cannot deactivate their own account', function () {
    $actor = accountScreenAdmin();

    $this->actingAs($actor)
        ->put(route('accounts.status.update', $actor), ['is_active' => false])
        ->assertRedirect();

    expect($actor->refresh()->is_active)->toBeTrue();
});

test('the last active administrator cannot be deactivated', function () {
    $target = accountScreenAdmin();
    $actor = accountScreenAdmin();

    // Keep the actor's loaded role for authorization, but remove it from the
    // database so the target is genuinely the last active administrator.
    $actor->load('roles');
    $actor->roles()->detach();

    $this->actingAs($actor)
        ->put(route('accounts.status.update', $target), ['is_active' => false])
        ->assertRedirect();

    expect($target->refresh()->is_active)->toBeTrue();
});

test('the accounts list exposes each account active status', function () {
    User::factory()->create(['name' => 'AAA Active Account', 'is_active' => true]);
    User::factory()->create(['name' => 'ZZZ Inactive Account', 'is_active' => false]);

    $this->actingAs(accountScreenAdmin())
        ->get(route('accounts.index', ['search' => 'AAA Active Account']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accounts.data', 1)
            ->where('accounts.data.0.is_active', true)
        );

    $this->actingAs(accountScreenAdmin())
        ->get(route('accounts.index', ['search' => 'ZZZ Inactive Account']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accounts.data', 1)
            ->where('accounts.data.0.is_active', false)
        );
});

test('an administrator can link an unlinked account to an employee', function () {
    $account = User::factory()->create();
    $employee = Employee::factory()->create();

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.employee.update', $account), [
            'employee_id' => $employee->id,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($account->refresh()->employee_id)->toBe($employee->id);
});

test('an administrator can replace and unlink an account employee', function () {
    $account = User::factory()->create();
    $first = Employee::factory()->create();
    $second = Employee::factory()->create();
    $account->employee()->associate($first);
    $account->save();

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.employee.update', $account), [
            'employee_id' => $second->id,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($account->refresh()->employee_id)->toBe($second->id);

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.employee.update', $account), [
            'employee_id' => null,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($account->refresh()->employee_id)->toBeNull();
});

test('an employee already linked to another account cannot be linked again', function () {
    $account = User::factory()->create();
    $other = User::factory()->create();
    $employee = Employee::factory()->create();
    $other->employee()->associate($employee);
    $other->save();

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.employee.update', $account), [
            'employee_id' => $employee->id,
        ])
        ->assertSessionHasErrors('employee_id');

    expect($account->refresh()->employee_id)->toBeNull();
});

test('the employee field must be present when linking an account', function () {
    $account = User::factory()->create();
    $employee = Employee::factory()->create();
    $account->employee()->associate($employee);
    $account->save();

    $this->actingAs(accountScreenAdmin())
        ->put(route('accounts.employee.update', $account), [])
        ->assertSessionHasErrors('employee_id');

    expect($account->refresh()->employee_id)->toBe($employee->id);
});

test('an account without administrator access cannot link an employee', function () {
    $account = User::factory()->create();
    $employee = Employee::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('accounts.employee.update', $account), [
            'employee_id' => $employee->id,
        ])
        ->assertForbidden();

    expect($account->refresh()->employee_id)->toBeNull();
});
