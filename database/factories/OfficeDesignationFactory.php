<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\WorkflowStepType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeDesignation>
 */
class OfficeDesignationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'office_id' => Office::factory(),
            'workflow_step_type_id' => WorkflowStepType::factory(),
            'employee_id' => Employee::factory(),
            'is_deputy' => false,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    /**
     * Place a deputy on the same step.
     */
    public function deputy(): static
    {
        return $this->state(fn (): array => ['is_deputy' => true]);
    }

    /**
     * A designation that no longer answers its step.
     */
    public function ended(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'ends_at' => now()->subDay()->toDateString(),
        ]);
    }
}
