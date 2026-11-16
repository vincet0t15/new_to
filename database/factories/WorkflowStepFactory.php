<?php

namespace Database\Factories;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowStep>
 */
class WorkflowStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'workflow_step_type_id' => WorkflowStepType::factory(),
            'label' => null,
            'office_id' => null,
            'sort_order' => 1,
            'auto_skip_creator' => true,
            'is_active' => true,
        ];
    }
}
