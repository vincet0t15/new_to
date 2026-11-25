<?php

use App\Models\Employee;
use App\Models\Permission;
use App\Models\TripTicket;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * An account holding only the given vehicle permissions.
 *
 * @param  array<int, string>  $permissions
 */
function vehicleUser(array $permissions = ['vehicles.view']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('guests are redirected to the login page', function () {
    $this->get(route('vehicles.index'))
        ->assertRedirect(route('login'));
});

test('accounts without the view permission cannot open the registry', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('vehicles.index'))
        ->assertForbidden();
});

test('the registry lists vehicles with their assigned drivers', function () {
    $driver = Employee::factory()->create([
        'first_name' => 'Juan',
        'middle_name' => null,
        'last_name' => 'Dela Cruz',
    ]);
    Vehicle::factory()->create([
        'plate_no' => 'ABC-1234',
        'driver_id' => $driver->id,
    ]);

    $this->actingAs(vehicleUser())
        ->get(route('vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/index')
            ->where('vehicles.data.0.plate_no', 'ABC-1234')
            ->where('vehicles.data.0.driver.full_name', 'Juan Dela Cruz')
            ->has('drivers')
            ->where('filters.search', ''));
});

test('a vehicle can be registered with an assigned driver', function () {
    $driver = Employee::factory()->create();

    $this->actingAs(vehicleUser(['vehicles.view', 'vehicles.create']))
        ->post(route('vehicles.store'), [
            'plate_no' => 'xyz-9876',
            'vehicle_type' => 'Van',
            'model' => 'Toyota HiAce',
            'capacity' => '15',
            'driver_id' => $driver->id,
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('vehicles', [
        'plate_no' => 'XYZ-9876',
        'vehicle_type' => 'Van',
        'model' => 'Toyota HiAce',
        'capacity' => 15,
        'driver_id' => $driver->id,
        'is_active' => true,
    ]);
});

test('duplicate plates are refused regardless of case', function () {
    Vehicle::factory()->create(['plate_no' => 'ABC-1234']);

    $this->actingAs(vehicleUser(['vehicles.view', 'vehicles.create']))
        ->post(route('vehicles.store'), [
            'plate_no' => 'abc-1234',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('plate_no');

    $this->assertDatabaseCount('vehicles', 1);
});

test('an inactive employee cannot be assigned as driver', function () {
    $driver = Employee::factory()->inactive()->create();

    $this->actingAs(vehicleUser(['vehicles.view', 'vehicles.create']))
        ->post(route('vehicles.store'), [
            'plate_no' => 'ABC-1234',
            'driver_id' => $driver->id,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('driver_id');
});

test('the assigned driver and active flag can be updated', function () {
    $vehicle = Vehicle::factory()->create(['plate_no' => 'ABC-1234']);
    $driver = Employee::factory()->create();

    $this->actingAs(vehicleUser(['vehicles.view', 'vehicles.update']))
        ->put(route('vehicles.update', $vehicle), [
            'plate_no' => 'ABC-1234',
            'vehicle_type' => 'Van',
            'driver_id' => $driver->id,
            'is_active' => false,
        ])
        ->assertRedirect();

    $vehicle->refresh();

    expect($vehicle->driver_id)->toBe($driver->id)
        ->and($vehicle->is_active)->toBeFalse();
});

test('a vehicle used by a trip ticket cannot be deleted', function () {
    $vehicle = Vehicle::factory()->create();
    TripTicket::factory()->create(['vehicle_id' => $vehicle->id]);

    $this->actingAs(vehicleUser(['vehicles.view', 'vehicles.delete']))
        ->delete(route('vehicles.destroy', $vehicle))
        ->assertRedirect();

    $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
});

test('an unused vehicle can be deleted', function () {
    $vehicle = Vehicle::factory()->create();

    $this->actingAs(vehicleUser(['vehicles.view', 'vehicles.delete']))
        ->delete(route('vehicles.destroy', $vehicle))
        ->assertRedirect();

    $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
});
