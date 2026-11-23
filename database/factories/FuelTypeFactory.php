<?php

namespace Database\Factories;

use App\Models\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelType>
 */
class FuelTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Diesel', 'Gasoline', 'Unleaded', 'Premium', 'Kerosene']).' '.fake()->unique()->numerify('##'),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
