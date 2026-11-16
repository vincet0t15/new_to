<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepType;
use Illuminate\Database\Seeder;

/**
 * Seed the routing configuration that the plan fixes as the starting point:
 *
 *   Default      Department Head → Budget → Mayor → HRMO
 *   Legislative  Department Head → Budget → Vice Mayor → HRMO
 *
 * Nobody is designated by this seeder. A designation is an operational fact
 * about a person, not configuration, so the Administrator assigns the primary
 * holders and deputies; until then the route preview reports the missing
 * designee instead of letting a submission stall silently.
 *
 * Everything is first-or-create. Re-running the seeder after an Administrator
 * has edited a chain must not overwrite the edit, so existing workflows keep
 * their steps and existing step types keep their wording.
 */
class WorkflowSeeder extends Seeder
{
    /**
     * Seed the default chain and the Legislative override.
     */
    public function run(): void
    {
        $offices = $this->supportOffices();
        $types = $this->stepTypes();

        $default = Workflow::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Travel Order Workflow',
                'description' => 'The fallback route for every office: Department Head, Budget, Mayor, HRMO.',
                'is_default' => true,
                'is_active' => true,
            ],
        );

        if ($default->wasRecentlyCreated) {
            $this->addSteps($default, [
                [$types['department_head'], null],
                [$types['budget_review'], $offices['budget']->id],
                [$types['mayor_approval'], $offices['mayor']->id],
                [$types['hrmo_review'], $offices['hrmo']->id],
            ]);
        }

        $legislative = Workflow::firstOrCreate(
            ['slug' => 'legislative'],
            [
                'name' => 'Legislative Override',
                'description' => 'The Legislative Office replaces the mayoral step with the Vice Mayor.',
                'is_default' => false,
                'is_active' => true,
            ],
        );

        if ($legislative->wasRecentlyCreated) {
            $this->addSteps($legislative, [
                [$types['department_head'], null],
                [$types['budget_review'], $offices['budget']->id],
                [$types['vice_mayor_approval'], $offices['vice_mayor']->id],
                [$types['hrmo_review'], $offices['hrmo']->id],
            ]);

            $offices['legislative']->update(['workflow_id' => $legislative->id]);
        }
    }

    /**
     * The offices the seeded chains point at.
     *
     * These are the only offices the seeder creates. Everything else in the
     * organization remains the Administrator's data to enter.
     *
     * @return array<string, Office>
     */
    private function supportOffices(): array
    {
        return [
            'budget' => Office::firstOrCreate(
                ['office_name' => 'Budget Office'],
                ['office_code' => 'BUDGET'],
            ),
            'mayor' => Office::firstOrCreate(
                ['office_name' => "Mayor's Office"],
                ['office_code' => 'MAYOR'],
            ),
            'vice_mayor' => Office::firstOrCreate(
                ['office_name' => 'Office of the Vice Mayor'],
                ['office_code' => 'VMAYOR'],
            ),
            'hrmo' => Office::firstOrCreate(
                ['office_name' => 'Human Resource Management Office'],
                ['office_code' => 'HRMO'],
            ),
            'legislative' => Office::firstOrCreate(
                ['office_name' => 'Legislative Office'],
                ['office_code' => 'LEG'],
            ),
        ];
    }

    /**
     * The reusable step definitions both chains are built from.
     *
     * @return array<string, WorkflowStepType>
     */
    private function stepTypes(): array
    {
        return [
            'department_head' => WorkflowStepType::firstOrCreate(
                ['slug' => WorkflowStepType::DEPARTMENT_HEAD_SLUG],
                [
                    'name' => 'Department Head Approval',
                    'signature_label' => 'Recommending Approval',
                    'signature_title' => 'Department Head',
                    'is_signatory' => true,
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ),
            'budget_review' => WorkflowStepType::firstOrCreate(
                ['slug' => 'budget_review'],
                [
                    'name' => 'Budget Review',
                    'signature_label' => 'Budget Review',
                    'signature_title' => 'Budget Officer',
                    'is_signatory' => true,
                    'sort_order' => 2,
                    'is_active' => true,
                ],
            ),
            'mayor_approval' => WorkflowStepType::firstOrCreate(
                ['slug' => 'mayor_approval'],
                [
                    'name' => 'Mayor Approval',
                    'signature_label' => 'Approved',
                    'signature_title' => 'City Mayor',
                    'is_signatory' => true,
                    'sort_order' => 3,
                    'is_active' => true,
                ],
            ),
            'vice_mayor_approval' => WorkflowStepType::firstOrCreate(
                ['slug' => 'vice_mayor_approval'],
                [
                    'name' => 'Vice Mayor Approval',
                    'signature_label' => 'Approved',
                    'signature_title' => 'City Vice Mayor',
                    'is_signatory' => true,
                    'sort_order' => 4,
                    'is_active' => true,
                ],
            ),
            'hrmo_review' => WorkflowStepType::firstOrCreate(
                ['slug' => 'hrmo_review'],
                [
                    'name' => 'HRMO Review',
                    'signature_label' => 'Reviewed',
                    'signature_title' => 'HRMO',
                    'is_signatory' => true,
                    'sort_order' => 5,
                    'is_active' => true,
                ],
            ),
        ];
    }

    /**
     * @param  array<int, array{0: WorkflowStepType, 1: int|null}>  $steps
     */
    private function addSteps(Workflow $workflow, array $steps): void
    {
        foreach ($steps as $index => [$type, $officeId]) {
            $workflow->steps()->create([
                'workflow_step_type_id' => $type->id,
                'label' => null,
                'office_id' => $officeId,
                'sort_order' => $index + 1,
                'auto_skip_creator' => true,
                'is_active' => true,
            ]);
        }
    }
}
