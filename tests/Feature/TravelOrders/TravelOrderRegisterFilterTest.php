<?php

use App\Enums\SystemRole;
use App\Enums\TravelOrderStatus;
use App\Models\Role;
use App\Models\TravelOrder;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function registerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(
        Role::query()->where('slug', SystemRole::Employee->value)->sole(),
    );

    return $user;
}

test('the register filters by status without exposing another account\'s orders', function () {
    $user = registerUser();

    $draft = TravelOrder::factory()->create([
        'created_by' => $user->id,
        'purpose' => 'Annual conference',
        'status' => TravelOrderStatus::Draft,
    ]);
    TravelOrder::factory()->create([
        'created_by' => $user->id,
        'purpose' => 'Site inspection',
        'status' => TravelOrderStatus::Submitted,
    ]);
    TravelOrder::factory()->create([
        'created_by' => registerUser()->id,
        'purpose' => 'Foreign training',
        'status' => TravelOrderStatus::Draft,
    ]);

    $this->actingAs($user)
        ->get(route('travel-orders.index', ['status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('travel-orders/index')
            ->has('travelOrders.data', 1)
            ->where('travelOrders.data.0.id', $draft->id)
            ->where('filters.status', 'draft')
            ->has('statuses', 7)
            ->where('statuses.0.value', 'draft')
        );
});

test('the status filter and the search box compose', function () {
    $user = registerUser();

    TravelOrder::factory()->create([
        'created_by' => $user->id,
        'purpose' => 'Budget coordination',
        'status' => TravelOrderStatus::Draft,
    ]);
    $match = TravelOrder::factory()->create([
        'created_by' => $user->id,
        'purpose' => 'Annual conference',
        'status' => TravelOrderStatus::Draft,
    ]);

    $this->actingAs($user)
        ->get(route('travel-orders.index', [
            'status' => 'draft',
            'search' => 'conference',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('travelOrders.data', 1)
            ->where('travelOrders.data.0.id', $match->id)
            ->where('filters.status', 'draft')
            ->where('filters.search', 'conference')
        );
});

test('an unknown status leaves the register unfiltered', function () {
    $user = registerUser();

    TravelOrder::factory()->count(2)->create([
        'created_by' => $user->id,
        'status' => TravelOrderStatus::Draft,
    ]);

    $this->actingAs($user)
        ->get(route('travel-orders.index', ['status' => 'not-a-status']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('travelOrders.data', 2)
            ->where('filters.status', '')
        );
});
