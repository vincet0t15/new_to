<?php

use App\Enums\TravelOrderStatus;
use App\Services\TravelOrderApprovalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the department head history keeps an order after it was acted on', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);

    // The work queue drops the order as soon as the step is cleared.
    $this->actingAs($chain['headUser'])
        ->get(route('department-head.inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/inbox')
            ->has('deliveries.data', 0)
        );

    // The history keeps it, with the action the account took.
    $this->actingAs($chain['headUser'])
        ->get(route('department-head.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/history')
            ->has('historyItems.data', 1)
            ->where('historyItems.data.0.id', $order->id)
            ->where('historyItems.data.0.your_action', 'Approved')
            ->where('historyItems.data.0.your_action_event', 'approved')
            ->where('historyItems.data.0.delivery_status', 'acted')
            ->where('historyItems.data.0.status', TravelOrderStatus::Submitted->value)
            ->where(
                'historyItems.data.0.url',
                "/department-head/travel-orders/{$order->id}",
            )
        );
});

test('the approvals history lists an order as waiting once it reaches the account', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    // Nothing has reached the budget office yet.
    $this->actingAs($chain['budgetUser'])
        ->get(route('approvals.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/history')
            ->has('historyItems.data', 0)
        );

    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);

    $this->actingAs($chain['budgetUser'])
        ->get(route('approvals.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/history')
            ->has('historyItems.data', 1)
            ->where('historyItems.data.0.id', $order->id)
            ->where('historyItems.data.0.your_action', 'Waiting')
            ->where('historyItems.data.0.your_action_event', 'pending')
            ->where('historyItems.data.0.delivery_status', 'pending')
            ->where(
                'historyItems.data.0.url',
                "/approvals/travel-orders/{$order->id}",
            )
        );
});

test('an order that is still pending appears in the history as waiting', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/history')
            ->has('historyItems.data', 1)
            ->where('historyItems.data.0.id', $order->id)
            ->where('historyItems.data.0.your_action', 'Waiting')
            ->where('historyItems.data.0.step_label', 'Department Head')
        );
});
