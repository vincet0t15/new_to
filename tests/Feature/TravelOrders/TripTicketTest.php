<?php

use App\Enums\SystemRole;
use App\Enums\TravelOrderStatus;
use App\Models\Employee;
use App\Models\Office;
use App\Models\TransportationMode;
use App\Models\TravelOrder;
use App\Models\TripTicket;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripTicketService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * An order in the given state, with the account that filed it.
 *
 * @return array{order: TravelOrder, user: User}
 */
function tripTicketOrder(
    TravelOrderStatus $status = TravelOrderStatus::Approved,
    bool $governmentVehicle = true,
): array {
    $office = Office::factory()->create();
    $employee = routingEmployee($office);
    $user = routingUser(SystemRole::Employee, $employee);

    $order = TravelOrder::factory()->create([
        'employee_id' => $employee->id,
        'office_id' => $office->id,
        'created_by' => $user->id,
        'status' => $status,
        'purpose' => 'Attend the regional summit',
        'date_from' => '2026-12-01',
        'date_to' => '2026-12-03',
    ]);

    $mode = TransportationMode::factory()->create([
        'name' => $governmentVehicle ? 'Government Vehicle' : 'Private Vehicle',
        'is_government' => $governmentVehicle,
        'is_active' => true,
    ]);
    $order->transportationModes()->attach($mode->id);

    return compact('order', 'user');
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tripTicketPayload(array $overrides = []): array
{
    return array_merge([
        'vehicle_plate_no' => 'ABC-1234',
        'vehicle_type' => 'Van',
        'driver_id' => null,
        'fuel_type' => 'Diesel',
        'fuel_efficiency' => '10',
        'distance_km' => '120',
        'round_trip' => true,
        'odometer_start' => '1000',
    ], $overrides);
}

it('prepares a numbered trip ticket when a government vehicle is used', function (TravelOrderStatus $status) {
    $context = tripTicketOrder($status);

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.store', $context['order']),
        tripTicketPayload(),
    )->assertRedirect();

    $ticket = $context['order']->tripTickets()->sole();

    expect($ticket->ticket_no)->toMatch('/^TT-\d{4}-\d{5}$/');
    expect($ticket->status->value)->toBe('issued');
    expect($ticket->liters)->toBe('24.00');
    expect($ticket->round_trip)->toBeTrue();
})->with([
    'draft' => TravelOrderStatus::Draft,
    'submitted' => TravelOrderStatus::Submitted,
    'approved' => TravelOrderStatus::Approved,
]);

it('renders the trip ticket create page for the order creator', function () {
    $context = tripTicketOrder();

    $this->actingAs($context['user'])->get(
        route('travel-orders.trip-tickets.create', $context['order']),
    )->assertInertia(fn (Assert $page): Assert => $page
        ->component('trip-tickets/create')
        ->where('travelOrder.id', $context['order']->id)
        ->has('vehicles')
        ->has('drivers')
    );
});

it('sends the creator back to the order when no government vehicle is used', function () {
    $context = tripTicketOrder(TravelOrderStatus::Approved, false);

    $this->actingAs($context['user'])->get(
        route('travel-orders.trip-tickets.create', $context['order']),
    )->assertRedirect(route('travel-orders.show', $context['order']));
});

it('refuses a ticket when no government vehicle is used', function () {
    $context = tripTicketOrder(TravelOrderStatus::Approved, false);

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.store', $context['order']),
        tripTicketPayload(),
    )->assertSessionHasErrors('travel_order_id');

    $this->assertDatabaseCount('trip_tickets', 0);
});

it('refuses a second ticket for the same plate on one order', function () {
    $context = tripTicketOrder();

    app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.store', $context['order']),
        tripTicketPayload(),
    )->assertSessionHasErrors('vehicle_plate_no');

    $this->assertDatabaseCount('trip_tickets', 1);
});

it('copies the registered vehicle plate, type and assigned driver onto a new ticket', function () {
    $context = tripTicketOrder();
    $driver = Employee::factory()->create();
    $vehicle = Vehicle::factory()->create([
        'plate_no' => 'FLEET-01',
        'vehicle_type' => 'Coaster',
        'driver_id' => $driver->id,
        'is_active' => true,
    ]);

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.store', $context['order']),
        tripTicketPayload([
            'vehicle_id' => $vehicle->id,
            'vehicle_plate_no' => 'IGNORED-1',
            'vehicle_type' => '',
            'driver_id' => null,
        ]),
    )->assertRedirect();

    $ticket = $context['order']->tripTickets()->sole();

    expect($ticket->vehicle_id)->toBe($vehicle->id)
        ->and($ticket->vehicle_plate_no)->toBe('FLEET-01')
        ->and($ticket->vehicle_type)->toBe('Coaster')
        ->and($ticket->driver_id)->toBe($driver->id);
});

it('allows the assigned driver to be overridden for one trip', function () {
    $context = tripTicketOrder();
    $assigned = Employee::factory()->create();
    $actual = Employee::factory()->create();
    $vehicle = Vehicle::factory()->create([
        'plate_no' => 'FLEET-02',
        'driver_id' => $assigned->id,
        'is_active' => true,
    ]);

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.store', $context['order']),
        tripTicketPayload([
            'vehicle_id' => $vehicle->id,
            'vehicle_plate_no' => 'IGNORED-2',
            'driver_id' => $actual->id,
        ]),
    )->assertRedirect();

    $ticket = $context['order']->tripTickets()->sole();

    expect($ticket->driver_id)->toBe($actual->id)
        ->and($ticket->vehicle_plate_no)->toBe('FLEET-02');
});

it('still accepts a manual plate when no registered vehicle is selected', function () {
    $context = tripTicketOrder();

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.store', $context['order']),
        tripTicketPayload([
            'vehicle_id' => null,
            'vehicle_plate_no' => 'MANUAL-99',
        ]),
    )->assertRedirect();

    $ticket = $context['order']->tripTickets()->sole();

    expect($ticket->vehicle_id)->toBeNull()
        ->and($ticket->vehicle_plate_no)->toBe('MANUAL-99');
});

it('completes an issued ticket with the post-trip figures', function () {
    $context = tripTicketOrder();
    $ticket = app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.complete', [
            $context['order'],
            $ticket,
        ]),
        [
            'odometer_end' => '1250.50',
            'fuel_consumed' => '22.50',
            'remarks' => 'Arrived safely.',
        ],
    )->assertRedirect();

    $ticket->refresh();

    expect($ticket->status->value)->toBe('completed');
    expect($ticket->odometer_end)->toBe('1250.50');
    expect($ticket->fuel_consumed)->toBe('22.50');
    expect($ticket->completed_by)->toBe($context['user']->id);
    expect($ticket->completed_at)->not->toBeNull();
});

it('refuses an ending odometer below the starting reading', function () {
    $context = tripTicketOrder();
    $ticket = app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.complete', [
            $context['order'],
            $ticket,
        ]),
        ['odometer_end' => '900'],
    )->assertSessionHasErrors('odometer_end');

    expect($ticket->refresh()->status->value)->toBe('issued');
});

it('cancels an issued ticket and then allows deleting it', function () {
    $context = tripTicketOrder();
    $ticket = app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.cancel', [
            $context['order'],
            $ticket,
        ]),
        ['cancel_reason' => 'The trip was postponed.'],
    )->assertRedirect();

    expect($ticket->refresh()->status->value)->toBe('cancelled');

    $this->actingAs($context['user'])->delete(
        route('travel-orders.trip-tickets.destroy', [
            $context['order'],
            $ticket,
        ]),
    )->assertRedirect(route('travel-orders.show', $context['order']));

    $this->assertDatabaseCount('trip_tickets', 0);
});

it('refuses to delete a ticket that is still issued', function () {
    $context = tripTicketOrder();
    $ticket = app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    $this->actingAs($context['user'])->delete(
        route('travel-orders.trip-tickets.destroy', [
            $context['order'],
            $ticket,
        ]),
    )->assertSessionHasErrors('status');

    $this->assertDatabaseCount('trip_tickets', 1);
});

it('refuses a completed ticket from being edited or cancelled', function () {
    $context = tripTicketOrder();
    $ticket = app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    app(TripTicketService::class)->complete($ticket, [], $context['user']);

    $this->actingAs($context['user'])->get(
        route('travel-orders.trip-tickets.edit', [
            $context['order'],
            $ticket,
        ]),
    )->assertRedirect(
        route('travel-orders.trip-tickets.show', [
            $context['order'],
            $ticket,
        ]),
    );

    $this->actingAs($context['user'])->post(
        route('travel-orders.trip-tickets.cancel', [
            $context['order'],
            $ticket,
        ]),
    )->assertSessionHasErrors('status');

    expect($ticket->refresh()->status->value)->toBe('completed');
});

it('keeps the trip ticket to the order creator', function () {
    $context = tripTicketOrder();
    $ticket = app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    $stranger = routingUser(SystemRole::Employee, routingEmployee());

    $this->actingAs($stranger)->get(
        route('travel-orders.trip-tickets.show', [
            $context['order'],
            $ticket,
        ]),
    )->assertForbidden();

    $this->actingAs($stranger)->post(
        route('travel-orders.trip-tickets.store', $context['order']),
        tripTicketPayload(['vehicle_plate_no' => 'XYZ-9876']),
    )->assertForbidden();
});

it('renders the trip ticket and print pages', function () {
    $context = tripTicketOrder();
    $ticket = app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    $this->actingAs($context['user'])->get(
        route('travel-orders.trip-tickets.show', [
            $context['order'],
            $ticket,
        ]),
    )->assertInertia(fn (Assert $page): Assert => $page
        ->component('trip-tickets/show')
        ->where('tripTicket.id', $ticket->id)
        ->where('travelOrder.id', $context['order']->id)
    );

    $this->actingAs($context['user'])->get(
        route('travel-orders.trip-tickets.print', [
            $context['order'],
            $ticket,
        ]),
    )->assertInertia(fn (Assert $page): Assert => $page
        ->component('trip-tickets/print')
        ->where('tripTicket.id', $ticket->id)
        ->where('letterhead.republic', config('travel-orders.letterhead.republic'))
        ->where('letterhead.municipality', config('travel-orders.letterhead.municipality'))
    );
});

it('lists the tickets on the travel order page', function () {
    $context = tripTicketOrder(TravelOrderStatus::Submitted);
    app(TripTicketService::class)->create(
        $context['order'],
        tripTicketPayload(),
        $context['user'],
    );

    $this->actingAs($context['user'])->get(
        route('travel-orders.show', $context['order']),
    )->assertInertia(fn (Assert $page): Assert => $page
        ->component('travel-orders/show')
        ->where('can.createTripTicket', false)
        ->has('travelOrder.trip_tickets', 1)
    );
});

it('does not leave a ticket behind when the order row is removed', function () {
    $context = tripTicketOrder();
    $ticket = TripTicket::factory()->create([
        'travel_order_id' => $context['order']->id,
    ]);

    $context['order']->delete();

    $this->assertDatabaseMissing('trip_tickets', ['id' => $ticket->id]);
});
