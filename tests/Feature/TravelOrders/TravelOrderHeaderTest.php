<?php

use App\Enums\TravelOrderChangeType;
use App\Enums\TravelOrderStatus;
use App\Models\ExpenseType;
use App\Models\FundSource;
use App\Models\Location;
use App\Models\TransportationMode;
use App\Models\TravelClassification;
use App\Models\TravelOrder;
use App\Services\TravelOrderApprovalService;
use App\Services\TravelOrderResubmissionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * The minimum header a draft needs to satisfy the request rules.
 *
 * @return array<string, mixed>
 */
function headerPayload(): array
{
    return [
        'travel_classification_id' => TravelClassification::factory()->create()->id,
        'fund_source_id' => FundSource::factory()->create()->id,
        'origin_id' => Location::factory()->create()->id,
        'destination_id' => Location::factory()->create()->id,
        'official_station_id' => Location::factory()->create()->id,
        'purpose' => 'Attend the municipal planning session.',
        'date_from' => '2026-12-01',
        'date_to' => '2026-12-02',
        'status' => 'draft',
    ];
}

test('the create form offers the configured header lookups', function () {
    $chain = routingChain();
    $classification = TravelClassification::factory()->create(['name' => 'Official Business']);
    $fundSource = FundSource::factory()->create(['name' => 'General Fund']);
    $location = Location::factory()->create(['name' => 'Manila City Hall']);
    $expenseType = ExpenseType::factory()->create(['name' => 'Hotel Accommodation']);
    $mode = TransportationMode::factory()->create(['name' => 'Van']);

    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('travel-orders/create')
            ->where('options.classifications.0.id', $classification->id)
            ->where('options.classifications.0.name', 'Official Business')
            ->where('options.fundSources', [])
            ->where('options.locations.0.name', $location->name)
            ->where('options.expenseTypes.0.name', $expenseType->name)
            ->where('options.transportationModes.0.name', $mode->name)
        );
});

test('storing a draft persists the header lookups mirrors and pivots', function () {
    $chain = routingChain();
    $classification = TravelClassification::factory()->create();
    $fundSource = FundSource::factory()->create();
    $origin = Location::factory()->create();
    $destination = Location::factory()->create();
    $station = Location::factory()->create();
    $hotel = ExpenseType::factory()->create(['name' => 'Hotel Accommodation']);
    $others = ExpenseType::factory()->create(['name' => 'Others']);
    $mode = TransportationMode::factory()->create();

    $this->actingAs($chain['filer'])
        ->post(route('travel-orders.store'), [
            'travel_classification_id' => $classification->id,
            'origin_id' => $origin->id,
            'destination_id' => $destination->id,
            'official_station_id' => $station->id,
            'purpose' => 'Attend the municipal planning session.',
            'date_from' => '2026-12-01',
            'date_to' => '2026-12-02',
            'expense_type_ids' => [$hotel->id, $others->id],
            'transportation_mode_ids' => [$mode->id],
            'other_expenses' => 'Parking and toll fees',
            'status' => 'draft',
        ])
        ->assertRedirect();

    $order = TravelOrder::query()->sole();

    expect($order->travel_classification_id)->toBe($classification->id)
        ->and($order->travel_classification)->toBe($classification->name)
        ->and($order->fund_source_id)->toBeNull()
        ->and($order->fund_source)->toBeNull()
        ->and($order->origin_id)->toBe($origin->id)
        ->and($order->destination_id)->toBe($destination->id)
        ->and($order->destination)->toBe($destination->name)
        ->and($order->official_station_id)->toBe($station->id)
        ->and($order->other_expenses)->toBe('Parking and toll fees')
        ->and($order->expenseTypes->pluck('id')->all())->toBe([$hotel->id, $others->id])
        ->and($order->transportationModes->pluck('id')->all())->toBe([$mode->id]);
});

test('the show payload exposes loaded lookups at their snake_case keys', function () {
    $chain = routingChain();
    $classification = TravelClassification::factory()->create(['name' => 'Official Business']);
    $fundSource = FundSource::factory()->create(['name' => 'General Fund']);
    $origin = Location::factory()->create(['name' => 'Municipal Hall']);
    $destination = Location::factory()->create(['name' => 'Provincial Capitol']);
    $station = Location::factory()->create(['name' => 'Municipal Hall Annex']);
    $hotel = ExpenseType::factory()->create(['name' => 'Hotel Accommodation']);
    $mode = TransportationMode::factory()->create(['name' => 'Government Vehicle']);

    $order = TravelOrder::factory()->create([
        'employee_id' => $chain['filerEmployee']->id,
        'office_id' => $chain['office']->id,
        'created_by' => $chain['filer']->id,
        'workflow_id' => null,
        'status' => TravelOrderStatus::Draft,
        'travel_classification_id' => $classification->id,
        'fund_source_id' => $fundSource->id,
        'origin_id' => $origin->id,
        'destination_id' => $destination->id,
        'official_station_id' => $station->id,
        'travel_classification' => $classification->name,
        'fund_source' => $fundSource->name,
    ]);

    $order->expenseTypes()->attach($hotel->id);
    $order->transportationModes()->attach($mode->id);

    // Eloquent serialises a loaded relation to the same snake_case key as the
    // legacy text column. The React detail screen relies on this shape, so a
    // backend change that renames or drops a relation must fail here first.
    $this->actingAs($chain['filer'])
        ->get(route('travel-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('travelOrder.travel_classification.name', 'Official Business')
            ->where('travelOrder.fund_source.name', 'General Fund')
            ->where('travelOrder.origin.name', 'Municipal Hall')
            ->where('travelOrder.destination_location.name', 'Provincial Capitol')
            ->where('travelOrder.official_station.name', 'Municipal Hall Annex')
            ->where('travelOrder.expense_types.0.name', 'Hotel Accommodation')
            ->where('travelOrder.transportation_modes.0.name', 'Government Vehicle')
        );
});

test('the header requires a classification and a destination', function () {
    $chain = routingChain();

    $this->actingAs($chain['filer'])
        ->post(route('travel-orders.store'), [
            'purpose' => 'Attend the municipal planning session.',
            'date_from' => '2026-12-01',
            'date_to' => '2026-12-02',
        ])
        ->assertSessionHasErrors(['travel_classification_id', 'destination_id']);

    expect(TravelOrder::query()->count())->toBe(0);
});

test('choosing others requires the other expenses text', function () {
    $chain = routingChain();
    $others = ExpenseType::factory()->create(['name' => 'Others']);

    $this->actingAs($chain['filer'])
        ->post(route('travel-orders.store'), [
            ...headerPayload(),
            'expense_type_ids' => [$others->id],
        ])
        ->assertSessionHasErrors('other_expenses');
});

test('updating a draft replaces the header selections', function () {
    $chain = routingChain();
    $order = TravelOrder::factory()->create([
        'employee_id' => $chain['filerEmployee']->id,
        'office_id' => $chain['office']->id,
        'created_by' => $chain['filer']->id,
        'workflow_id' => null,
        'status' => TravelOrderStatus::Draft,
    ]);

    $payload = headerPayload();
    $hotel = ExpenseType::factory()->create(['name' => 'Hotel Accommodation']);
    $mode = TransportationMode::factory()->create();

    $this->actingAs($chain['filer'])
        ->put(route('travel-orders.update', $order), [
            ...$payload,
            'expense_type_ids' => [$hotel->id],
            'transportation_mode_ids' => [$mode->id],
        ])
        ->assertRedirect();

    $order->refresh();

    expect($order->travel_classification_id)->toBe($payload['travel_classification_id'])
        ->and($order->destination_id)->toBe($payload['destination_id'])
        ->and($order->expenseTypes->pluck('id')->all())->toBe([$hotel->id])
        ->and($order->transportationModes->pluck('id')->all())->toBe([$mode->id]);
});

test('a returned order records changed header fields as a material revision', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    app(TravelOrderApprovalService::class)
        ->returnTo($order, $chain['headUser'], 'Move the trip to the new venue.');

    $destination = Location::factory()->create();
    $expenseType = ExpenseType::factory()->create(['name' => 'Hotel Accommodation']);

    $order->update([
        'destination_id' => $destination->id,
        'destination' => $destination->name,
    ]);
    $order->expenseTypes()->attach($expenseType->id);

    $order = app(TravelOrderResubmissionService::class)
        ->resubmit($order->fresh(), $chain['filer'], 'Moved to the new venue.');

    $revision = $order->revisions()
        ->where('revision_number', $order->revision_number - 1)
        ->sole();

    expect($revision->change_type)->toBe(TravelOrderChangeType::Material)
        ->and($revision->changed_fields)->toContain('destination_id')
        ->and($revision->changed_fields)->toContain('expense_type_ids');
});
