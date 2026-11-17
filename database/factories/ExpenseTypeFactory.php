<?php

namespace Database\Factories;

use App\Models\ExpenseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseType>
 */
class ExpenseTypeFactory extends Factory
{
    protected $model = ExpenseType::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'description' => fake()->sentence(), 'is_default' => false, 'is_transportation' => fake()->boolean(), 'is_active' => true];
    }
}
