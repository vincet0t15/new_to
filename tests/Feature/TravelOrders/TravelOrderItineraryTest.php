<?php

use App\Enums\SystemRole;
use App\Enums\TravelOrderStatus;
use App\Models\Location;
use App\Models\Office;
use App\Models\PerDiemConfiguration;
use App\Models\TransportationFare;
use App\Models\TransportationMode;
use App\Models\TravelOrder;
use App\Models\TravelOrderItinerary;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * A draft with its creator, origin, and destination.
 *
 * @return array{order: TravelOrder, user: User, origin: Location, destination: Location}
 */
function itineraryDraft(): array
{
    $office = Office::factory()->create();
    $employee = routingEmployee($office);
    $user = routingUser(SystemRole::Employee, $employee);
    $origin = Location::factory()->create();
    $destination = Location::factory()->create();

    $order = TravelOrder::factory()->create([
        'employee_id' => $employee->id,
        'office_id' => $office->id,
        'created_by' => $user->id,
        'origin_id' => $origin->id,
        'destination_id' => $destination->id,
        'status' => TravelOrderStatus::Draft,
        'date_from' => '2026-12-01',
        'date_to' => '2026-12-02',
    ]);

    return compact('order', 'user', 'origin', 'destination');
}

/**
 * @param  array{order: TravelOrder, user: User, origin: Location, destination: Location}  $context
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function itineraryPayload(array $context, array $overrides = []): array
{
    return array_merge([
        'date_from' => '2026-12-01',
        'date_to' => '2026-12-01',
        'departure_time' => '08:00',
        'arrival_time' => '17:00',
        'from_location_id' => $context['origin']->id,
        'to_location_id' => $context['destination']->id,
        'activity' => 'Attend the municipal planning session',
        'reason' => null,
        'transportation_mode_id' => null,
        'transportation_fare' => '150.00',
        'transportation_fare_is_auto' => false,
        'per_diem' => '500.00',
        'per_diem_is_auto' => false,
        'registration_fee' => '0.00',
    ], $overrides);
}

it('adds an itinerary leg as the order creator', function () {
    $context = itineraryDraft();

    $response = $this->actingAs($context['user'])->post(
        route('travel-orders.itineraries.store', $context['order']),
        itineraryPayload($context),
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('travel_order_itineraries', [
        'travel_order_id' => $context['order']->id,
        'from_location_id' => $context['origin']->id,
        'to_location_id' => $context['destination']->id,
        'activity' => 'Attend the municipal planning session',
        'transportation_fare' => '150.00',
    ]);
});

it('resolves the automatic per diem from the active configuration', function () {
    $context = itineraryDraft();

    $configuration = PerDiemConfiguration::factory()->create([
        'daily_rate' => '500.00',
        'effective_from' => null,
        'effective_until' => null,
        'is_active' => true,
    ]);

    $configuration->locations()->sync([$context['destination']->id]);

    $this->actingAs($context['user'])->post(
        route('travel-orders.itineraries.store', $context['order']),
        itineraryPayload($context, [
            'per_diem' => null,
            'per_diem_is_auto' => true,
        ]),
    )->assertRedirect();

    $itinerary = $context['order']->itineraries()->sole();

    expect($itinerary->per_diem)->toBe('500.00');
    expect($itinerary->per_diem_is_auto)->toBeTrue();
});

it('resolves the automatic fare from the configured destination rate', function () {
    $context = itineraryDraft();

    $mode = TransportationMode::factory()->create([
        'is_government' => false,
        'fix_cost' => '999.00',
    ]);

    TransportationFare::create([
        'transportation_mode_id' => $mode->id,
        'location_id' => $context['destination']->id,
        'amount' => '275.00',
    ]);

    $this->actingAs($context['user'])->post(
        route('travel-orders.itineraries.store', $context['order']),
        itineraryPayload($context, [
            'transportation_mode_id' => $mode->id,
            'transportation_fare' => null,
            'transportation_fare_is_auto' => true,
        ]),
    )->assertRedirect();

    $itinerary = $context['order']->itineraries()->sole();

    expect($itinerary->transportation_fare)->toBe('275.00');
    expect($itinerary->transportation_fare_is_auto)->toBeTrue();
});

it('refuses itinerary changes from an account that did not file the order', function () {
    $context = itineraryDraft();
    $stranger = routingUser(SystemRole::Employee, routingEmployee());

    $this->actingAs($stranger)->post(
        route('travel-orders.itineraries.store', $context['order']),
        itineraryPayload($context),
    )->assertForbidden();

    $this->assertDatabaseCount('travel_order_itineraries', 0);
});

it('refuses itinerary changes once the order is submitted', function () {
    $context = itineraryDraft();
    $context['order']->update(['status' => TravelOrderStatus::Submitted]);

    $this->actingAs($context['user'])->post(
        route('travel-orders.itineraries.store', $context['order']),
        itineraryPayload($context),
    )->assertForbidden();
});

it('updates and deletes an itinerary leg', function () {
    $context = itineraryDraft();

    $itinerary = TravelOrderItinerary::create([
        ...itineraryPayload($context),
        'travel_order_id' => $context['order']->id,
        'transportation_fare_is_auto' => false,
        'per_diem_is_auto' => false,
    ]);

    $this->actingAs($context['user'])->put(
        route('travel-orders.itineraries.update', [
            $context['order'],
            $itinerary,
        ]),
        itineraryPayload($context, [
            'activity' => 'Attend the budget hearing',
            'per_diem' => '650.00',
        ]),
    )->assertRedirect();

    expect($itinerary->refresh()->activity)->toBe('Attend the budget hearing');
    expect($itinerary->per_diem)->toBe('650.00');

    $this->actingAs($context['user'])->delete(
        route('travel-orders.itineraries.destroy', [
            $context['order'],
            $itinerary,
        ]),
    )->assertRedirect();

    $this->assertDatabaseCount('travel_order_itineraries', 0);
});

it('shows the itinerary and its choices on the order page', function () {
    $context = itineraryDraft();

    TravelOrderItinerary::create([
        ...itineraryPayload($context),
        'travel_order_id' => $context['order']->id,
        'transportation_fare_is_auto' => false,
        'per_diem_is_auto' => false,
    ]);

    $this->actingAs($context['user'])->get(
        route('travel-orders.show', $context['order']),
    )->assertInertia(fn (Assert $page): Assert => $page
        ->component('travel-orders/show')
        ->where('can.manageItinerary', true)
        ->has('travelOrder.itineraries', 1)
        ->has('options.locations')
        ->has('options.transportationModes')
    );
});
