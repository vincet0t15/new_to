<?php

use App\Enums\SystemRole;
use App\Models\Location;
use App\Models\PerDiemConfiguration;
use App\Models\PerDiemMealRule;
use App\Models\Role;
use App\Models\TransportationMode;
use App\Models\User;
use App\Repositories\Eloquent\EloquentPerDiemConfigurationRepository;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(
        Role::query()->where('slug', SystemRole::Admin->value)->sole(),
    );
});

test('transportation mode saves and replaces destination fares', function () {
    $firstLocation = Location::factory()->create();
    $secondLocation = Location::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('transportation-modes.store'), [
            'name' => 'Intercity bus',
            'description' => 'Public transportation',
            'fix_cost' => '0.00',
            'is_government' => false,
            'is_active' => true,
            'fares' => [
                ['location_id' => $firstLocation->id, 'amount' => '125.50'],
            ],
        ])
        ->assertRedirect(route('transportation-modes.index'));

    $mode = TransportationMode::query()->where('name', 'Intercity bus')->sole();

    $this->assertDatabaseHas('transportation_fares', [
        'transportation_mode_id' => $mode->id,
        'location_id' => $firstLocation->id,
        'amount' => '125.50',
    ]);

    $this->actingAs($this->admin)
        ->put(route('transportation-modes.update', $mode), [
            'name' => 'Intercity bus',
            'description' => 'Public transportation',
            'fix_cost' => '0.00',
            'is_government' => false,
            'is_active' => true,
            'fares' => [
                ['location_id' => $secondLocation->id, 'amount' => '210.00'],
            ],
        ])
        ->assertRedirect(route('transportation-modes.index'));

    expect($mode->fares()->pluck('location_id')->all())->toBe([$secondLocation->id]);

    $this->actingAs($this->admin)
        ->put(route('transportation-modes.update', $mode), [
            'name' => 'Intercity bus',
            'description' => 'Public transportation',
            'fix_cost' => '0.00',
            'is_government' => false,
            'is_active' => true,
            'fares' => [
                ['location_id' => $secondLocation->id, 'amount' => '210.00'],
                ['location_id' => $secondLocation->id, 'amount' => '215.00'],
            ],
        ])
        ->assertSessionHasErrors('fares.1.location_id');

    expect($mode->fares()->count())->toBe(1);
});

test('per diem rules contribute to their rate and reject incompatible cutoffs', function () {
    $legacy = PerDiemConfiguration::factory()->create([
        'name' => 'Legacy daily rate',
        'daily_rate' => '125.00',
    ]);

    $legacyRate = (new EloquentPerDiemConfigurationRepository)
        ->paginate('Legacy daily rate')
        ->getCollection()
        ->sole()
        ->daily_rate;

    expect($legacyRate)->toBe('125.00');

    $location = Location::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('per-diem-configurations.store'), [
            'name' => 'Regional daily rate',
            'location_ids' => [$location->id],
            'effective_from' => '2026-01-01',
            'effective_until' => null,
            'is_active' => true,
        ])
        ->assertRedirect(route('per-diem-configurations.index'));

    $configuration = PerDiemConfiguration::query()->where('name', 'Regional daily rate')->sole();

    expect($configuration->locations()->pluck('locations.id')->all())->toBe([$location->id]);

    $this->actingAs($this->admin)
        ->post(route('per-diem-configurations.meal-rules.store', $configuration), [
            'meal_type' => 'breakfast',
            'amount' => '350.00',
            'cutoff_time' => '08:00',
            'sort_order' => 1,
        ])
        ->assertRedirect();

    expect((new EloquentPerDiemConfigurationRepository)->dailyRate($configuration))
        ->toBe('350.00');

    $this->actingAs($this->admin)
        ->post(route('per-diem-configurations.meal-rules.store', $configuration), [
            'meal_type' => 'incidental',
            'amount' => '100.00',
            'cutoff_time' => '08:00',
            'sort_order' => 2,
        ])
        ->assertSessionHasErrors('cutoff_time');

    expect(PerDiemMealRule::query()->where('per_diem_configuration_id', $configuration->id)->count())
        ->toBe(1);
});

test('a meal rule cannot be edited through another configuration route', function () {
    $first = PerDiemConfiguration::factory()->create();
    $second = PerDiemConfiguration::factory()->create();
    $rule = $first->perDiemMealRules()->create([
        'meal_type' => 'breakfast',
        'name' => 'Breakfast',
        'amount' => '300.00',
        'cutoff_time' => '08:00',
        'sort_order' => 1,
    ]);

    $this->actingAs($this->admin)
        ->get(route('per-diem-configurations.meal-rules.edit', [$second, $rule]))
        ->assertNotFound();
});

test('a location referenced by setup fares cannot be deleted', function () {
    $location = Location::factory()->create();
    $mode = TransportationMode::factory()->create();
    $mode->fares()->create(['location_id' => $location->id, 'amount' => '80.00']);

    $this->actingAs($this->admin)
        ->delete(route('locations.destroy', $location))
        ->assertRedirect();

    $this->assertDatabaseHas('locations', ['id' => $location->id]);
});
