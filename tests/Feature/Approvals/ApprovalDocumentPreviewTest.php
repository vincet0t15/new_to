<?php

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
