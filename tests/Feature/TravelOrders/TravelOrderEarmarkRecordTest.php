<?php

use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Models\FundSource;
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

    $order = $service->earmark($order, $chain['budgetUser'], [
        'earmark_number' => 'EN-2026-001',
        'remarks' => 'Funds allocated under GAA.',
    ]);

    expect($order->earmarked_by_user_id)->toBe($chain['budgetUser']->id)
        ->and($order->earmarked_at)->not->toBeNull()
        ->and($order->earmark_number)->toBe('EN-2026-001')
        ->and($order->earmark_remarks)->toBe('Funds allocated under GAA.')
        ->and($order->current_step_index)->toBe(1)
        ->and($order->status)->toBe(TravelOrderStatus::Submitted);

    $event = $order->actions()->where('event', WorkflowEvent::Earmarked->value)->sole();
    expect($event->actor_user_id)->toBe($chain['budgetUser']->id)
        ->and($event->step_index)->toBe(1)
        ->and($event->remarks)->toBe('Funds allocated under GAA.')
        ->and($event->meta)->toMatchArray(['earmark_number' => 'EN-2026-001']);
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

test('hrmo holder can record release details on hrmo step', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);
    $filingNumber = $order->to_number;

    $order = $service->approve($order, $chain['headUser']);
    $order = $service->approve($order, $chain['budgetUser']);

    expect($order->current_step_index)->toBe(2);

    $order = $service->record($order, $chain['hrmoUser'], [
        'remarks' => 'Recorded and released.',
        'distributed' => true,
    ]);

    expect($order->to_number)->toBe($filingNumber)
        ->and($order->control_no)->toBeNull()
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
            'distributed' => true,
        ]);
});

test('record action ignores caller supplied numbers', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);
    $filingNumber = $order->to_number;

    $order = $service->approve($order, $chain['headUser']);
    $order = $service->approve($order, $chain['budgetUser']);

    $this->actingAs($chain['hrmoUser'])
        ->from(route('approvals.travel-orders.show', $order))
        ->post(route('approvals.travel-orders.record', $order), [
            'control_no' => 'CTRL-X',
            'to_number' => 'TO-X',
        ])
        ->assertRedirect(route('approvals.travel-orders.show', $order));

    expect($order->refresh()->to_number)->toBe($filingNumber)
        ->and($order->control_no)->toBeNull()
        ->and($order->recorded_at)->not->toBeNull();
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
            ->where('can.assignFundSource', true)
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
            ->where('can.assignFundSource', false)
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
            'remarks' => 'Too early.',
        ])
        ->assertRedirect(route('approvals.inbox'))
        ->assertSessionHasErrors('action');

    expect($order->refresh()->recorded_at)->toBeNull()
        ->and($order->control_no)->toBeNull();
});

test('budget holder can assign fund source on budget review step', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);
    $fundSource = FundSource::factory()->create(['name' => 'Special Fund']);

    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);

    $order = $service->assignFundSource($order, $chain['budgetUser'], $fundSource->id, 'Assigned by budget.');

    expect($order->fund_source_id)->toBe($fundSource->id)
        ->and($order->fund_source)->toBe('Special Fund')
        ->and($order->fund_source_assigned_by_user_id)->toBe($chain['budgetUser']->id)
        ->and($order->fund_source_assigned_at)->not->toBeNull()
        ->and($order->current_step_index)->toBe(1);

    $event = $order->actions()->where('event', WorkflowEvent::FundSourceAssigned->value)->sole();
    expect($event->actor_user_id)->toBe($chain['budgetUser']->id)
        ->and($event->meta)->toMatchArray([
            'fund_source_id' => $fundSource->id,
            'fund_source' => 'Special Fund',
        ]);
});
