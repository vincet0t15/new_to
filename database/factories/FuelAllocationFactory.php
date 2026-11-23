<?php

namespace Database\Factories;

use App\Models\FuelAllocation;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelAllocation>
 */
class FuelAllocationFactory extends Factory
{
    protected $model = FuelAllocation::class;

    public function definition(): array
    {
        return [
            'office_id' => Office::factory(),
            'allocation_date' => now()->toDateString(),
            'reference_no' => 'FA-'.fake()->numerify('####'),
            'fuel_type' => fake()->randomElement(['Diesel', 'Gasoline', 'Unleaded']),
            'allocated_liters' => fake()->randomFloat(2, 10, 500),
            'allocated_amount' => fake()->randomFloat(2, 1000, 30000),
            'remarks' => fake()->optional()->sentence(),
            'status' => FuelAllocation::STATUS_ACTIVE,
            'created_by' => User::factory(),
        ];
    }

    public function voided(): static
    {
        return $this->state(fn () => [
            'status' => FuelAllocation::STATUS_VOIDED,
            'void_reason' => 'Voided for test',
            'voided_at' => now(),
        ]);
    }
}
