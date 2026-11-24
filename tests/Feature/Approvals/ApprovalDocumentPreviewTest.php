<?php

use App\Models\FuelType;
use App\Models\TripTicket;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the approval view supplies the printed documents an approver previews', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.travel-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/show')
            ->where('travelOrder.id', $order->id)
            ->where('letterhead.republic', config('travel-orders.letterhead.republic'))
            ->has('signatories.budget_office_approval')
            ->has('travelOrder.itineraries')
            ->where('itinerarySignatories.certified.name', null)
            ->where('itinerarySignatories.certified.title', 'Municipal Budget Officer')
            ->where('itinerarySignatories.approved.name', null)
            ->where('itinerarySignatories.approved.title', 'Municipal Mayor')
        );
});

test('the approval view supplies printable trip tickets with their document relations', function () {
    $chain = routingChain();
    $order = routingSubmit($chain);
    $fuelType = FuelType::factory()->create(['name' => 'Biodiesel']);
    $ticket = TripTicket::factory()->create([
        'travel_order_id' => $order->id,
        'driver_id' => $chain['filerEmployee']->id,
        'fuel_type_id' => $fuelType->id,
        'created_by' => $chain['filer']->id,
    ]);

    $this->actingAs($chain['headUser'])
        ->get(route('department-head.travel-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/show')
            ->has('tripTickets', 1)
            ->where('tripTickets.0.id', $ticket->id)
            ->where('tripTickets.0.ticket_no', $ticket->ticket_no)
            ->where('tripTickets.0.driver.full_name', $chain['filerEmployee']->full_name)
            ->where('tripTickets.0.creator.name', $chain['filer']->name)
            ->where('tripTickets.0.fuel_type_record.name', 'Biodiesel')
        );
});
