<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'suffix' => fake()->optional(0.1)->randomElement(['Jr.', 'Sr.', 'III', 'IV']),
            'position_id' => Position::factory(),
            'office_id' => Office::factory(),
            'employment_type_id' => EmploymentType::factory(),
            'contact_number' => fake()->optional()->phoneNumber(),
            'monthly_salary' => fake()->optional()->randomFloat(2, 10000, 120000),
            'image_path' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
