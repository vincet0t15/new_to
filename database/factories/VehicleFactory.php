<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plate_no' => strtoupper(fake()->unique()->bothify('???-####')),
            'vehicle_type' => fake()->randomElement(['Van', 'Car', 'Truck']),
            'model' => fake()->optional()->randomElement(['Toyota HiAce', 'Isuzu D-Max', 'Nissan Urvan']),
            'capacity' => fake()->numberBetween(2, 30),
            'driver_id' => null,
            'is_active' => true,
        ];
    }
}
