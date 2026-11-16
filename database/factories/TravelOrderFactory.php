<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Office;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelOrder>
 */
class TravelOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'control_no' => null,
            'to_number' => null,
            'employee_id' => Employee::factory(),
            'office_id' => Office::factory(),
            'created_by' => User::factory(),
            'workflow_id' => Workflow::factory(),
            'purpose' => fake()->sentence(),
            'destination' => fake()->city(),
            'date_from' => now()->addDays(1)->toDateString(),
            'date_to' => now()->addDays(3)->toDateString(),
            'fund_source' => null,
            'travel_classification' => null,
            'status' => 'draft',
            'routed_steps' => null,
            'current_step_index' => null,
            'recipient_user_id' => null,
            'submitted_at' => null,
        ];
    }
}
