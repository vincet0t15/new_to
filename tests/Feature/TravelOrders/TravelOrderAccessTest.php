<?php

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('an employee can open their travel order list', function () {
    $user = User::factory()->create();
    $user->assignRole(
        Role::query()->where('slug', SystemRole::Employee->value)->sole(),
    );

    $this->actingAs($user)
        ->get(route('travel-orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('travel-orders/index')
            ->has('travelOrders.data', 0)
        );
});

test('an account without the travel order permission cannot open the list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('travel-orders.index'))
        ->assertForbidden();
});
