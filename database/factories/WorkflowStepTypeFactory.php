<?php

namespace Database\Factories;

use App\Models\WorkflowStepType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkflowStepType>
 */
class WorkflowStepTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->bothify('Step Type ???-####');

        return [
            'name' => $name,
            'slug' => Str::slug($name, '_'),
            'signature_label' => fake()->optional()->words(2, true),
            'signature_title' => fake()->optional()->jobTitle(),
            'is_signatory' => true,
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    /**
     * A step that only records receipt instead of an approval.
     */
    public function receipt(): static
    {
        return $this->state(fn (): array => ['is_signatory' => false]);
    }
}
