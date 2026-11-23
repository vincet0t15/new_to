<?php

use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Models\TravelOrder;
use App\Services\TravelOrderApprovalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('budget holder can record earmark on budget review step', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);

    expect($order->current_step_index)->toBe(1);

    $order = $service->earmark($order, $chain['budgetUser'], 'Funds allocated under GAA.');

    expect($order->earmarked_by_user_id)->toBe($chain['budgetUser']->id)
        ->and($order->earmarked_at)->not->toBeNull()
        ->and($order->earmark_remarks)->toBe('Funds allocated under GAA.')
        ->and($order->current_step_index)->toBe(1)
        ->and($order->status)->toBe(TravelOrderStatus::Submitted);

    $event = $order->actions()->where('event', WorkflowEvent::Earmarked->value)->sole();
    expect($event->actor_user_id)->toBe($chain['budgetUser']->id)
        ->and($event->step_index)->toBe(1)
        ->and($event->remarks)->toBe('Funds allocated under GAA.');
});

test('earmark action requires active holder and submitted order', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);
    $order = routingSubmit($chain);

    $this->actingAs($chain['budgetUser'])
        ->from(route('approvals.inbox'))
        ->post(route('approvals.travel-orders.earmark', $order), ['remarks' => 'Nope'])
        ->assertRedirect(route('approvals.inbox'))
        ->assertSessionHasErrors('action');

    expect($order->refresh()->earmarked_at)->toBeNull();
});

test('hrmo holder can record series control and distribution on hrmo step', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);
    $order = $service->approve($order, $chain['budgetUser']);

    expect($order->current_step_index)->toBe(2);

    $order = $service->record($order, $chain['hrmoUser'], [
        'control_no' => 'CTRL-2026-001',
        'to_number' => 'TO-2026-001',
        'remarks' => 'Recorded and released.',
        'distributed' => true,
    ]);

    expect($order->control_no)->toBe('CTRL-2026-001')
        ->and($order->to_number)->toBe('TO-2026-001')
        ->and($order->recorded_by_user_id)->toBe($chain['hrmoUser']->id)
        ->and($order->recorded_at)->not->toBeNull()
        ->and($order->recording_remarks)->toBe('Recorded and released.')
        ->and($order->distributed_at)->not->toBeNull()
        ->and($order->current_step_index)->toBe(2)
        ->and($order->status)->toBe(TravelOrderStatus::Submitted);

    $event = $order->actions()->where('event', WorkflowEvent::Recorded->value)->sole();
    expect($event->actor_user_id)->toBe($chain['hrmoUser']->id)
        ->and($event->step_index)->toBe(2)
        ->and($event->meta)->toMatchArray([
            'control_no' => 'CTRL-2026-001',
            'to_number' => 'TO-2026-001',
            'distributed' => true,
        ]);
});

test('record action validates unique control and to numbers', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    TravelOrder::factory()->create(['control_no' => 'CTRL-X', 'to_number' => 'TO-X']);

    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);
    $order = $service->approve($order, $chain['budgetUser']);

    $this->actingAs($chain['hrmoUser'])
        ->from(route('approvals.travel-orders.show', $order))
        ->post(route('approvals.travel-orders.record', $order), [
            'control_no' => 'CTRL-X',
            'to_number' => 'TO-X',
        ])
        ->assertRedirect(route('approvals.travel-orders.show', $order))
        ->assertSessionHasErrors(['control_no', 'to_number']);

    expect($order->refresh()->recorded_at)->toBeNull();
});

test('approval show exposes earmark and record abilities by step and permission', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);
    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);

    $this->actingAs($chain['budgetUser'])
        ->get(route('approvals.travel-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/show')
            ->where('can.earmark', true)
            ->where('can.record', false)
            ->where('can.approve', true)
        );

    $order = $service->approve($order, $chain['budgetUser']);

    $this->actingAs($chain['hrmoUser'])
        ->get(route('approvals.travel-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/show')
            ->where('can.earmark', false)
            ->where('can.record', true)
            ->where('can.acknowledge', true)
        );
});

test('record action requires active hrmo holder', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);

    $this->actingAs($chain['hrmoUser'])
        ->from(route('approvals.inbox'))
        ->post(route('approvals.travel-orders.record', $order), [
            'to_number' => 'TO-2026-999',
        ])
        ->assertRedirect(route('approvals.inbox'))
        ->assertSessionHasErrors('action');

    expect($order->refresh()->to_number)->toBeNull()
        ->and($order->recorded_at)->toBeNull();
});
