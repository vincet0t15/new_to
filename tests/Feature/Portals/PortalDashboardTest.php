<?php

use App\Enums\SystemRole;
use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Models\Role;
use App\Models\TravelOrder;
use App\Models\TravelOrderAction;
use App\Models\TravelOrderDelivery;
use App\Models\User;
use App\Services\TravelOrderApprovalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the employee dashboard counts only the account\'s own travel orders', function () {
    $chain = routingChain();
    routingSubmit($chain);

    TravelOrder::factory()->create([
        'created_by' => $chain['filer']->id,
        'office_id' => $chain['office']->id,
        'status' => TravelOrderStatus::Draft,
    ]);
    TravelOrder::factory()->create([
        'created_by' => $chain['filer']->id,
        'office_id' => $chain['office']->id,
        'status' => TravelOrderStatus::Approved,
    ]);
    TravelOrder::factory()->create([
        'created_by' => $chain['headUser']->id,
        'office_id' => $chain['office']->id,
        'status' => TravelOrderStatus::Draft,
    ]);

    $this->actingAs($chain['filer'])
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/employee/dashboard')
            ->where('stats.draft', 1)
            ->where('stats.submitted', 1)
            ->where('stats.returned', 0)
            ->where('stats.approved', 1)
        );
});

test('a returned order moves from in progress to returned on the employee dashboard', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    app(TravelOrderApprovalService::class)->returnTo(
        $order,
        $chain['headUser'],
        'Please correct the travel dates.',
    );

    $this->actingAs($chain['filer'])
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/employee/dashboard')
            ->where('stats.submitted', 0)
            ->where('stats.returned', 1)
        );
});

test('the department head dashboard counts pending and processed work', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/department-head/dashboard')
            ->where('stats.pending', 1)
            ->where('stats.processed', 0)
            ->where('stats.returned', 0)
        );

    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/department-head/dashboard')
            ->where('stats.pending', 0)
            ->where('stats.processed', 1)
        );
});

test('the approvals inbox carries processing counts and portal inbox links', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);

    $this->actingAs($chain['budgetUser'])
        ->get(route('approvals.inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/inbox')
            ->where('stats.pending', 1)
            ->where('stats.processed', 0)
            ->where('stats.returned', 0)
            ->where('currentPortal', 'approvals')
            ->has('portals', 2)
            ->where('portals.0.key', 'approvals')
            ->where('portals.0.inboxUrl', '/approvals/inbox')
            ->where('portals.1.key', 'employee')
            ->where('portals.1.inboxUrl', null)
        );
});

test('an employee-only account has no processing portal or inbox link', function () {
    $user = User::factory()->create();
    $employeeRole = Role::query()
        ->where('slug', SystemRole::Employee->value)
        ->firstOrFail();
    $user->assignRole($employeeRole);

    $this->actingAs($user)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/employee/dashboard')
            ->where('currentPortal', 'employee')
            ->has('portals', 1)
            ->where('portals.0.inboxUrl', null)
        );
});

test('processing counts distinct orders when one account holds several steps', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    // The same person held two steps of one order and acted on both copies.
    TravelOrderDelivery::factory()->create([
        'travel_order_id' => $order->id,
        'user_id' => $chain['headUser']->id,
        'step_index' => 2,
        'status' => TravelOrderDeliveryStatus::Acted,
    ]);
    TravelOrderDelivery::query()
        ->where('travel_order_id', $order->id)
        ->where('user_id', $chain['headUser']->id)
        ->update(['status' => TravelOrderDeliveryStatus::Acted]);

    // And that order was sent back twice across a resubmission cycle.
    TravelOrderAction::factory()->count(2)->create([
        'travel_order_id' => $order->id,
        'actor_user_id' => $chain['headUser']->id,
        'event' => WorkflowEvent::Returned,
    ]);

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.pending', 0)
            ->where('stats.processed', 1)
            ->where('stats.returned', 1)
        );
});
