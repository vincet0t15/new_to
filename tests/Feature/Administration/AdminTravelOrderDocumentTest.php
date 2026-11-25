<?php

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\TravelOrder;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('an administrator can view the document of an order they do not own', function () {
    $order = TravelOrder::factory()->create();
    $admin = grantAdminPortal(User::factory()->create());
    $admin->givePermissionTo('travel-orders.view-all');

    expect($order->isOwnedBy($admin))->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.travel-orders.document', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/travel-orders/document')
            ->where('travelOrder.id', $order->id)
            ->has('letterhead')
            ->has('signatories')
            ->has('itinerarySignatories')
            ->has('tripTickets')
            ->where('urls.register', route('admin.travel-orders.index'))
        );
});

test('the document view is refused without view-all permission', function () {
    $order = TravelOrder::factory()->create();

    Role::query()
        ->where('slug', SystemRole::Admin->value)
        ->sole()
        ->syncPermissions([]);

    $user = grantAdminPortal(User::factory()->create());

    $this->actingAs($user)
        ->get(route('admin.travel-orders.document', $order))
        ->assertForbidden();
});

test('the document view is refused outside the admin portal', function () {
    $order = TravelOrder::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo('travel-orders.view-all');

    $this->actingAs($user)
        ->get(route('admin.travel-orders.document', $order))
        ->assertForbidden();
});
