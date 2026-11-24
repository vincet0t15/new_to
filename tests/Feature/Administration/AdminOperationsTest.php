<?php

use App\Enums\SystemRole;
use App\Models\OfficeDesignation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function adminOperationsUser(array $permissions = [], bool $stripRolePermissions = false): User
{
    if ($stripRolePermissions) {
        Role::query()
            ->where('slug', SystemRole::Admin->value)
            ->sole()
            ->syncPermissions([]);
    }

    $admin = grantAdminPortal(User::factory()->create());

    if ($permissions !== []) {
        $admin->givePermissionTo($permissions);
    }

    return $admin;
}

test('the stalled queue respects the configured threshold', function () {
    config()->set('travel-orders.approval.stalled_after_days', 7);

    $chain = routingChain();
    $order = routingSubmit($chain);
    $order->forceFill(['submitted_at' => now()->subDays(5)])->save();

    $admin = adminOperationsUser(['travel-orders.view-all']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stalledThresholdDays', 7)
            ->where('stalledTotal', 0)
            ->missing('stalledOrders.0')
        );
});

test('the admin dashboard lists submitted orders past the stalled threshold with their holder', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);
    $order->forceFill(['submitted_at' => now()->subDays(5)])->save();

    $admin = adminOperationsUser(['travel-orders.view-all']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/admin/dashboard')
            ->where('stalledThresholdDays', 3)
            ->where('stalledTotal', 1)
            ->where('stalledOrders.0.id', $order->id)
            ->where('stalledOrders.0.age_days', 5)
            ->where('stalledOrders.0.holder', $chain['headUser']->name)
        );
});

test('the impact preview lists current deliveries held by a designation', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $replacementEmployee = routingEmployee($chain['office']);
    $replacement = routingUser(SystemRole::DepartmentHead, $replacementEmployee);
    OfficeDesignation::factory()->create([
        'office_id' => $chain['office']->id,
        'workflow_step_type_id' => $chain['headType']->id,
        'employee_id' => $replacementEmployee->id,
    ]);

    $admin = adminOperationsUser(['designations.manage']);

    $this->actingAs($admin)
        ->get(route('designations.impact', $chain['headDesignation']))
        ->assertOk()
        ->assertJsonPath('designation.holder', $chain['headEmployee']->full_name)
        ->assertJsonPath('orders.0.id', $order->id)
        ->assertJsonPath('orders.0.holder', $chain['headUser']->name)
        ->assertJsonPath('candidates.0.id', $replacement->id);
});

test('an admin can bulk reassign affected orders without advancing the step', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);
    $stepIndex = $order->current_step_index;

    $replacementEmployee = routingEmployee($chain['office']);
    $replacement = routingUser(SystemRole::DepartmentHead, $replacementEmployee);

    $admin = adminOperationsUser([
        'travel-orders.view-all',
        'travel-orders.reassign',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.travel-orders.reassign-bulk'), [
            'travel_order_ids' => [$order->id],
            'to_user_id' => $replacement->id,
            'remarks' => 'Designation changed',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('travel_orders', [
        'id' => $order->id,
        'recipient_user_id' => $replacement->id,
        'current_step_index' => $stepIndex,
    ]);
    $this->assertDatabaseHas('travel_order_deliveries', [
        'travel_order_id' => $order->id,
        'step_index' => $stepIndex,
        'user_id' => $replacement->id,
        'status' => 'pending',
    ]);
    $this->assertDatabaseHas('travel_order_actions', [
        'travel_order_id' => $order->id,
        'event' => 'reassigned',
        'actor_user_id' => $admin->id,
    ]);
});

test('the impact preview requires designation management permission', function () {
    $chain = routingChain();
    $admin = adminOperationsUser(['travel-orders.view-all'], true);

    $this->actingAs($admin)
        ->get(route('designations.impact', $chain['headDesignation']))
        ->assertForbidden();
});

test('bulk reassignment requires the reassign permission', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);
    $replacement = routingUser(SystemRole::DepartmentHead, routingEmployee($chain['office']));
    $admin = adminOperationsUser(['travel-orders.view-all'], true);

    $this->actingAs($admin)
        ->post(route('admin.travel-orders.reassign-bulk'), [
            'travel_order_ids' => [$order->id],
            'to_user_id' => $replacement->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('travel_orders', [
        'id' => $order->id,
        'recipient_user_id' => $chain['headUser']->id,
    ]);
});

test('bulk reassignment rejects empty and invalid input', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);
    $admin = adminOperationsUser([
        'travel-orders.view-all',
        'travel-orders.reassign',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.travel-orders.reassign-bulk'), [
            'travel_order_ids' => [],
            'to_user_id' => 0,
        ])
        ->assertSessionHasErrors(['travel_order_ids', 'to_user_id']);

    $this->assertDatabaseHas('travel_orders', [
        'id' => $order->id,
        'recipient_user_id' => $chain['headUser']->id,
    ]);
});
