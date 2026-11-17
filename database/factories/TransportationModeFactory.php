<?php

namespace Database\Factories;

use App\Models\TransportationMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransportationMode>
 */
class TransportationModeFactory extends Factory
{
    protected $model = TransportationMode::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'is_government' => fake()->boolean(),
            'fix_cost' => fake()->randomFloat(2, 100, 5000),
            'is_active' => true,
        ];
    }
}
