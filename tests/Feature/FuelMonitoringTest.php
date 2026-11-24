<?php

use App\Enums\SystemRole;
use App\Enums\TravelOrderStatus;
use App\Enums\TripTicketStatus;
use App\Models\FuelAllocation;
use App\Models\FuelAllocationEvent;
use App\Models\FuelType;
use App\Models\Office;
use App\Models\TransportationMode;
use App\Models\TravelOrder;
use App\Models\TripTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function fuelMonitoringUser(): User
{
    $office = Office::factory()->create();
    $employee = routingEmployee($office);
    $user = routingUser(SystemRole::Admin, $employee);
    $user->givePermissionTo('travel-orders.view-all');

    return $user;
}

function createFuelTripTicket(array $overrides = []): TripTicket
{
    $orderOffice = $overrides['order_office'] ?? Office::factory()->create();
    $sourceOffice = $overrides['source_office'] ?? $orderOffice;
    $fuelType = $overrides['fuel_type'] ?? FuelType::factory()->create(['name' => 'Diesel', 'is_active' => true]);
    $employee = routingEmployee($orderOffice);
    $user = routingUser(SystemRole::Employee, $employee);

    $order = TravelOrder::factory()->create([
        'employee_id' => $employee->id,
        'office_id' => $orderOffice->id,
        'created_by' => $user->id,
        'status' => TravelOrderStatus::Approved,
        'purpose' => $overrides['purpose'] ?? 'Fuel monitoring test trip',
        'date_from' => '2026-12-01',
        'date_to' => '2026-12-02',
    ]);

    $mode = TransportationMode::factory()->create(['name' => 'Government Vehicle '.fake()->unique()->numerify('###'), 'is_government' => true, 'is_active' => true]);
    $order->transportationModes()->attach($mode->id);

    return TripTicket::query()->create(array_merge([
        'travel_order_id' => $order->id,
        'ticket_no' => $overrides['ticket_no'] ?? 'TT-FUEL-001',
        'status' => TripTicketStatus::Completed,
        'vehicle_plate_no' => 'ABC-1234',
        'vehicle_type' => 'Van',
        'purpose' => $order->purpose,
        'date_from' => $order->date_from,
        'date_to' => $order->date_to,
        'liters' => 20,
        'fuel_consumed' => 20,
        'fuel_price_per_liter' => 65,
        'fuel_cost' => 1300,
        'fuel_type_id' => $fuelType->id,
        'fuel_type' => $fuelType->name,
        'fuel_source_office_id' => $sourceOffice->id,
        'issued_by' => $user->id,
        'issued_at' => now(),
        'completed_at' => now(),
    ], $overrides['ticket'] ?? []));
}

test('fuel monitoring groups balance by office and fuel type', function () {
    $admin = fuelMonitoringUser();
    $office = Office::factory()->create(['office_name' => 'Mayor Office']);
    $diesel = FuelType::factory()->create(['name' => 'Diesel', 'is_active' => true]);
    $gas = FuelType::factory()->create(['name' => 'Unleaded Gasoline 91 RON', 'is_active' => true]);

    FuelAllocation::factory()->create([
        'office_id' => $office->id,
        'fuel_type_id' => $diesel->id,
        'fuel_type' => $diesel->name,
        'allocated_liters' => 100,
        'allocated_amount' => 6500,
        'status' => FuelAllocation::STATUS_ACTIVE,
    ]);
    FuelAllocation::factory()->create([
        'office_id' => $office->id,
        'fuel_type_id' => $gas->id,
        'fuel_type' => $gas->name,
        'allocated_liters' => 50,
        'allocated_amount' => 3500,
        'status' => FuelAllocation::STATUS_ACTIVE,
    ]);

    createFuelTripTicket([
        'order_office' => $office,
        'source_office' => $office,
        'fuel_type' => $diesel,
        'ticket' => ['ticket_no' => 'TT-DIESEL-1', 'fuel_consumed' => 20, 'fuel_cost' => 1300, 'liters' => 20],
    ]);
    createFuelTripTicket([
        'order_office' => $office,
        'source_office' => $office,
        'fuel_type' => $gas,
        'ticket' => ['ticket_no' => 'TT-GAS-1', 'fuel_consumed' => 10, 'fuel_cost' => 700, 'liters' => 10],
    ]);

    $response = $this->actingAs($admin)->get(route('admin.fuel-monitoring.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('approvals/fuel-monitoring/index')
        ->has('byOffice', 1)
        ->where('byOffice.0.office_name', 'Mayor Office')
        ->where('byOffice.0.actual_liters', fn ($v) => (float) $v === 30.0)
        ->where('byOffice.0.remaining_liters', fn ($v) => (float) $v === 120.0)
        ->has('byOffice.0.fuel_types', 2)
    );
});

test('fuel monitoring can filter borrowed fuel records', function () {
    $admin = fuelMonitoringUser();
    $borrower = Office::factory()->create(['office_name' => 'Borrower Office']);
    $source = Office::factory()->create(['office_name' => 'Source Office']);
    $diesel = FuelType::factory()->create(['name' => 'Diesel', 'is_active' => true]);

    createFuelTripTicket([
        'order_office' => $borrower,
        'source_office' => $source,
        'fuel_type' => $diesel,
        'ticket' => ['ticket_no' => 'TT-BORROW-1'],
    ]);
    createFuelTripTicket([
        'order_office' => $borrower,
        'source_office' => $borrower,
        'fuel_type' => $diesel,
        'ticket' => ['ticket_no' => 'TT-OWN-1'],
    ]);

    $response = $this->actingAs($admin)->get(route('admin.fuel-monitoring.index', ['borrowed_only' => 1]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('approvals/fuel-monitoring/index')
        ->has('tickets.data', 1)
        ->where('tickets.data.0.ticket_no', 'TT-BORROW-1')
        ->has('borrowedByOffice', 1)
        ->where('borrowedByOffice.0.borrower_office_name', 'Borrower Office')
        ->where('borrowedByOffice.0.source_office_name', 'Source Office')
    );
});

test('fuel allocation index filters by fuel type', function () {
    $office = Office::factory()->create();
    $employee = routingEmployee($office);
    $admin = routingUser(SystemRole::Admin, $employee);
    $admin->givePermissionTo('fuel-allocations.view');

    $diesel = FuelType::factory()->create(['name' => 'Diesel', 'is_active' => true]);
    $gas = FuelType::factory()->create(['name' => 'Premium Gasoline 95 RON', 'is_active' => true]);

    FuelAllocation::factory()->create(['office_id' => $office->id, 'fuel_type_id' => $diesel->id, 'fuel_type' => $diesel->name, 'reference_no' => 'FA-D']);
    FuelAllocation::factory()->create(['office_id' => $office->id, 'fuel_type_id' => $gas->id, 'fuel_type' => $gas->name, 'reference_no' => 'FA-G']);

    $response = $this->actingAs($admin)->get(route('admin.fuel-allocations.index', ['fuel_type_id' => $diesel->id]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/fuel-allocations/index')
        ->has('allocations.data', 1)
        ->where('allocations.data.0.reference_no', 'FA-D')
    );
});

test('fuel transfer records paired events and warns on insufficient source balance', function () {
    $source = Office::factory()->create(['office_name' => 'Source']);
    $target = Office::factory()->create(['office_name' => 'Target']);
    $diesel = FuelType::factory()->create(['name' => 'Diesel', 'is_active' => true]);
    $admin = fuelMonitoringUser();
    $admin->givePermissionTo('fuel-allocations.view', 'fuel-allocations.manage');

    FuelAllocation::factory()->create([
        'office_id' => $source->id,
        'fuel_type_id' => $diesel->id,
        'fuel_type' => $diesel->name,
        'allocated_liters' => 10,
        'allocated_amount' => 650,
        'status' => FuelAllocation::STATUS_ACTIVE,
        'type' => FuelAllocation::TYPE_ALLOCATION,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.fuel-allocations.store'), [
        'type' => FuelAllocation::TYPE_TRANSFER,
        'office_id' => $target->id,
        'source_office_id' => $source->id,
        'allocation_date' => '2026-11-23',
        'reference_no' => 'TR-001',
        'fuel_type_id' => $diesel->id,
        'fuel_type' => $diesel->name,
        'allocated_liters' => 20,
        'allocated_amount' => 1300,
        'remarks' => 'Borrow test',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('warning');

    $this->assertDatabaseCount('fuel_allocations', 3);
    $this->assertDatabaseHas('fuel_allocations', ['office_id' => $source->id, 'type' => 'transfer', 'allocated_liters' => '-20.00']);
    $this->assertDatabaseHas('fuel_allocations', ['office_id' => $target->id, 'type' => 'transfer', 'allocated_liters' => '20.00']);
    expect(FuelAllocationEvent::query()->whereIn('event', ['transfer_debit', 'transfer_credit'])->count())->toBe(2);
});

test('admin dashboard shows fuel low balance alerts', function () {
    $admin = fuelMonitoringUser();
    $office = Office::factory()->create(['office_name' => 'Alert Office']);
    $diesel = FuelType::factory()->create(['name' => 'Diesel', 'is_active' => true]);

    FuelAllocation::factory()->create([
        'office_id' => $office->id,
        'fuel_type_id' => $diesel->id,
        'fuel_type' => $diesel->name,
        'allocated_liters' => 10,
        'allocated_amount' => 500,
        'status' => FuelAllocation::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('portals/admin/dashboard')
        ->has('fuelAlerts', 1)
        ->where('fuelAlerts.0.office_name', 'Alert Office')
        ->where('fuelAlerts.0.fuel_type', 'Diesel')
        ->has('recentOrders')
        ->has('fuelSummary')
        ->where('fuelSummary.active_allocations', 1)
    );
});

test('admin dashboard groups fuel balances without raw fuel type columns in group by', function () {
    $admin = fuelMonitoringUser();
    $office = Office::factory()->create(['office_name' => 'Grouping Office']);
    $diesel = FuelType::factory()->create(['name' => 'Diesel', 'is_active' => true]);

    FuelAllocation::factory()->create([
        'office_id' => $office->id,
        'fuel_type_id' => $diesel->id,
        'fuel_type' => $diesel->name,
        'allocated_liters' => 100,
        'allocated_amount' => 6500,
        'status' => FuelAllocation::STATUS_ACTIVE,
    ]);
    FuelAllocation::factory()->create([
        'office_id' => $office->id,
        'fuel_type_id' => null,
        'fuel_type' => 'Legacy Oil',
        'allocated_liters' => 25,
        'allocated_amount' => 1000,
        'status' => FuelAllocation::STATUS_ACTIVE,
    ]);

    $queries = [];
    DB::listen(static function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/admin/dashboard')
            ->where('fuelSummary.active_allocations', 2)
        );

    $groupedFuelQueries = array_values(array_filter(
        $queries,
        static fn (string $sql): bool => stripos($sql, 'group by') !== false
            && stripos($sql, 'fuel_types') !== false,
    ));

    expect($groupedFuelQueries)->not->toBeEmpty();

    foreach ($groupedFuelQueries as $sql) {
        expect($sql)->toContain('resolved_fuel_type');
        expect($sql)->not->toMatch('/group by.*fuel_types\.name/is');
    }
});
