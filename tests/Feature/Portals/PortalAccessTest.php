<?php

use App\Enums\Portal;
use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use App\Services\PortalResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Create an account assigned the given system roles.
 *
 * @param  array<int, SystemRole>  $roles
 */
function portalUser(array $roles): User
{
    $user = User::factory()->create();

    foreach ($roles as $role) {
        $user->assignRole(
            Role::query()->where('slug', $role->value)->sole(),
        );
    }

    return $user;
}

test('guests are redirected to the login page from every portal', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with([
    'employee' => ['/employee/dashboard'],
    'department head' => ['/department-head/dashboard'],
    'approvals' => ['/approvals/inbox'],
    'admin' => ['/admin/dashboard'],
]);

test('every signed-in account can enter the employee portal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/employee/dashboard')
            ->where('currentPortal', Portal::Employee->value)
        );
});

test('a department head can enter the department head portal', function () {
    $user = portalUser([SystemRole::DepartmentHead]);

    $this->actingAs($user)
        ->get(route('department-head.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/department-head/dashboard')
            ->where('currentPortal', Portal::DepartmentHead->value)
        );
});

test('approver roles can enter the shared approvals portal', function (SystemRole $role) {
    $user = portalUser([$role]);

    $this->actingAs($user)
        ->get(route('approvals.inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/inbox')
            ->where('currentPortal', Portal::Approvals->value)
        );
})->with([
    'budget officer' => [SystemRole::BudgetOfficer],
    'mayor' => [SystemRole::Mayor],
    'hrmo' => [SystemRole::Hrmo],
]);

test('administrators can enter the admin portal', function (SystemRole $role) {
    $user = portalUser([$role]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/admin/dashboard')
            ->where('currentPortal', Portal::Admin->value)
        );
})->with([
    'admin' => [SystemRole::Admin],
    'super admin' => [SystemRole::SuperAdmin],
]);

test('accounts cannot enter a portal their roles do not grant', function (string $url) {
    $user = User::factory()->create();

    $this->actingAs($user)->get($url)->assertForbidden();
})->with([
    'department head portal' => ['/department-head/dashboard'],
    'approvals portal' => ['/approvals/inbox'],
    'admin portal' => ['/admin/dashboard'],
]);

test('an administrator is not an approver without the approver role', function () {
    $user = portalUser([SystemRole::Admin]);

    $this->actingAs($user)->get(route('approvals.inbox'))->assertForbidden();
});

test('a department head cannot enter the admin portal', function () {
    $user = portalUser([SystemRole::DepartmentHead]);

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('admin records require the admin portal even when the permission is granted', function () {
    $user = portalUser([SystemRole::Employee]);
    $user->givePermissionTo(['administration.view', 'offices.view']);

    $this->actingAs($user)->get(route('offices.index'))->assertForbidden();
});

test('admin screens report the admin portal as current', function () {
    $user = portalUser([SystemRole::Admin]);

    $this->actingAs($user)
        ->get(route('offices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentPortal', Portal::Admin->value)
        );
});

test('a multi-role account lands on its primary portal and can switch to the rest', function () {
    $user = portalUser([SystemRole::Employee, SystemRole::DepartmentHead]);

    $resolver = app(PortalResolver::class);

    expect($resolver->defaultFor($user))->toBe(Portal::DepartmentHead)
        ->and($resolver->availableFor($user))->toBe([
            Portal::DepartmentHead,
            Portal::Employee,
        ]);

    $this->actingAs($user)->get(route('department-head.dashboard'))->assertOk();
    $this->actingAs($user)->get(route('employee.dashboard'))->assertOk();
});

test('shared props list every portal the account may switch to', function () {
    $user = portalUser([SystemRole::Employee, SystemRole::DepartmentHead]);

    $this->actingAs($user)
        ->get(route('department-head.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('portals', 2)
            ->where('portals.0.key', Portal::DepartmentHead->value)
            ->where('portals.0.url', '/department-head/dashboard')
            ->where('portals.0.isCurrent', true)
            ->where('portals.1.key', Portal::Employee->value)
            ->where('portals.1.url', '/employee/dashboard')
            ->where('portals.1.isCurrent', false)
        );
});

test('signing in redirects to the primary portal', function (SystemRole $role, string $routeName) {
    $user = portalUser([$role]);

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
    ])->assertRedirect(route($routeName));

    $this->assertAuthenticatedAs($user);
})->with([
    'employee' => [SystemRole::Employee, 'employee.dashboard'],
    'department head' => [SystemRole::DepartmentHead, 'department-head.dashboard'],
    'budget officer' => [SystemRole::BudgetOfficer, 'approvals.inbox'],
    'mayor' => [SystemRole::Mayor, 'approvals.inbox'],
    'hrmo' => [SystemRole::Hrmo, 'approvals.inbox'],
    'admin' => [SystemRole::Admin, 'admin.dashboard'],
    'super admin' => [SystemRole::SuperAdmin, 'admin.dashboard'],
]);

test('the legacy dashboard redirects to the primary portal', function (SystemRole $role, string $routeName) {
    $user = portalUser([$role]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route($routeName));
})->with([
    'employee' => [SystemRole::Employee, 'employee.dashboard'],
    'department head' => [SystemRole::DepartmentHead, 'department-head.dashboard'],
    'budget officer' => [SystemRole::BudgetOfficer, 'approvals.inbox'],
    'mayor' => [SystemRole::Mayor, 'approvals.inbox'],
    'hrmo' => [SystemRole::Hrmo, 'approvals.inbox'],
    'admin' => [SystemRole::Admin, 'admin.dashboard'],
    'super admin' => [SystemRole::SuperAdmin, 'admin.dashboard'],
]);

test('signing in honours a deep link to a portal the account may enter', function () {
    $user = portalUser([SystemRole::Employee]);

    $this->withSession(['url.intended' => '/employee/dashboard'])
        ->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ])
        ->assertRedirect('/employee/dashboard');
});

test('signing in ignores a deep link to a portal the account may not enter', function () {
    $user = portalUser([SystemRole::Employee]);

    $this->withSession(['url.intended' => '/admin/offices'])
        ->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ])
        ->assertRedirect(route('employee.dashboard'));
});
