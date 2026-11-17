<?php

use App\Enums\SystemRole;
use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Exceptions\TravelOrderRoutingException;
use App\Models\Location;
use App\Models\TravelClassification;
use App\Models\TravelOrder;
use App\Notifications\TravelOrderNotification;
use App\Services\TravelOrderApprovalService;
use App\Services\TravelOrderResubmissionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function revChain(): array
{
    return routingChain();
}

function revDraft(array $chain): TravelOrder
{
    return routingDraft($chain);
}

function revSubmit(array $chain): TravelOrder
{
    return routingSubmit($chain);
}

test('return records origin, remarks, and opens a revision row', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    $order = app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Fix the dates.');

    expect($order->status)->toBe(TravelOrderStatus::Returned)
        ->and($order->returned_by_user_id)->toBe($chain['headUser']->id)
        ->and($order->returned_from_step_index)->toBe(0)
        ->and($order->returned_from_step)->toBe('Department Head')
        ->and($order->return_remarks)->toBe('Fix the dates.')
        ->and($order->returned_at)->not->toBeNull();

    expect($order->revisions)->toHaveCount(1)
        ->and($order->revisions->first()->return_remarks)->toBe('Fix the dates.');
});

test('return requires remarks', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    expect(fn () => app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], '   '))
        ->toThrow(TravelOrderRoutingException::class, 'Please explain');
});

test('a returned order appears on the Returned for Revision page', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach invitation.');

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.returned'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('travel-orders/returned')
            ->has('travelOrders.data', 1)
            ->where('travelOrders.data.0.id', $order->id)
        );
});

test('a returned order never appears in a processing inbox', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach invitation.');

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('deliveries.data', 0));
});

test('only the creator can edit a returned travel order', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach invitation.');

    $traveler = routingUser(SystemRole::Employee, routingEmployee($chain['office']));

    $this->actingAs($traveler)
        ->get(route('travel-orders.edit', $order))
        ->assertForbidden();
});

test('saving a returned travel order keeps it returned', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach invitation.');

    $classification = TravelClassification::factory()->create();
    $destination = Location::factory()->create();

    $this->actingAs($chain['filer'])
        ->put(route('travel-orders.update', $order), [
            'travel_classification_id' => $classification->id,
            'destination_id' => $destination->id,
            'purpose' => 'Revised purpose',
            'date_from' => '2026-12-01',
            'date_to' => '2026-12-02',
        ])
        ->assertRedirect();

    $order->refresh();

    expect($order->status)->toBe(TravelOrderStatus::Returned)
        ->and($order->purpose)->toBe('Revised purpose');
});

test('resubmission requires remarks', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach invitation.');

    $classification = TravelClassification::factory()->create();
    $destination = Location::factory()->create();

    $this->actingAs($chain['filer'])
        ->post(route('travel-orders.resubmit', $order), [
            'travel_classification_id' => $classification->id,
            'destination_id' => $destination->id,
            'purpose' => 'Revised purpose',
            'date_from' => '2026-12-01',
            'date_to' => '2026-12-02',
            'revision_remarks' => '   ',
        ])
        ->assertSessionHasErrors('revision_remarks');

    expect($order->refresh()->status)->toBe(TravelOrderStatus::Returned);
});

test('resume policy returns a correction to the step that sent it back', function () {
    config(['travel-orders.resubmission.policy' => 'resume']);

    $chain = revChain();
    $order = revSubmit($chain);

    // advance to budget step first
    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);

    $order->refresh();
    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['budgetUser'], 'Fix the dates.');

    // edit content
    $order->update(['purpose' => 'Revised purpose']);

    $order = app(TravelOrderResubmissionService::class)
        ->resubmit($order->fresh(), $chain['filer'], 'Fixed the dates.');

    expect($order->status)->toBe(TravelOrderStatus::Submitted)
        ->and($order->current_step_index)->toBe(1)
        ->and($order->revision_number)->toBe(2);
});

test('material changes restart at the earliest step reviewing the changed fields', function () {
    config(['travel-orders.resubmission.policy' => 'restart_from_first_affected']);

    $chain = revChain();
    $chain['headStep']->update(['reviewed_fields' => ['purpose']]);
    $chain['budgetStep']->update(['reviewed_fields' => ['fund_source_id']]);
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);
    $order->refresh();
    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['budgetUser'], 'Recheck the revised purpose.');

    $chain['headStep']->update(['reviewed_fields' => []]);
    $chain['budgetStep']->update(['reviewed_fields' => ['purpose', 'fund_source_id']]);
    $order->update(['purpose' => 'Revised purpose']);

    $order = app(TravelOrderResubmissionService::class)
        ->resubmit($order->fresh(), $chain['filer'], 'Updated the purpose.');

    expect($order->current_step_index)->toBe(0)
        ->and($order->routed_step_review_fields)->toBe([
            ['purpose'],
            ['fund_source_id'],
            [],
        ]);
});

test('material changes with no configured reviewing step restart at the first step', function () {
    config(['travel-orders.resubmission.policy' => 'restart_from_first_affected']);

    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);
    $order->refresh();
    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['budgetUser'], 'Recheck the purpose.');

    $order->update(['purpose' => 'Revised purpose']);

    $order = app(TravelOrderResubmissionService::class)
        ->resubmit($order->fresh(), $chain['filer'], 'Updated the purpose.');

    expect($order->current_step_index)->toBe(0);
});

test('resubmission increments the revision number and keeps the to number', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach invitation.');

    $originalNumber = $order->to_number;

    app(TravelOrderResubmissionService::class)
        ->resubmit($order->fresh(), $chain['filer'], 'Attached invitation.');

    $order->refresh();

    expect($order->revision_number)->toBe(2)
        ->and($order->to_number)->toBe($originalNumber);
});

test('previous approvals remain in history after resubmission', function () {
    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)->approve($order, $chain['headUser']);
    $order->refresh();

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['budgetUser'], 'Fix the dates.');
    $order->refresh();

    app(TravelOrderResubmissionService::class)
        ->resubmit($order, $chain['filer'], 'Fixed.');

    expect($order->fresh()->actions()->where('event', WorkflowEvent::Approved->value)->count())->toBe(1);
});

test('return notifies the creator', function () {
    Notification::fake();

    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach invitation.');

    Notification::assertSentTo($chain['filer'], TravelOrderNotification::class);
});

test('resubmission notifies the returning office', function () {
    Notification::fake();

    $chain = revChain();
    $order = revSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Please attach invitation.');

    $order->refresh();

    app(TravelOrderResubmissionService::class)
        ->resubmit($order, $chain['filer'], 'Attached invitation.');

    Notification::assertSentTo($chain['headUser'], TravelOrderNotification::class);
});
