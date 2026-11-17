<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Workflow;
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
 * Everything is first-or-create by its natural key. Re-running the seeder after
 * an Administrator has edited a chain must not overwrite the edit, so existing
 * workflows keep their steps and step types with seeded names keep their wording.
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
                [$types['department_head'], null, $this->departmentHeadReviewFields()],
                [$types['budget_review'], $offices['budget']->id, $this->budgetReviewFields()],
                [$types['mayor_approval'], $offices['mayor']->id, []],
                [$types['hrmo_review'], $offices['hrmo']->id, []],
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
                [$types['department_head'], null, $this->departmentHeadReviewFields()],
                [$types['budget_review'], $offices['budget']->id, $this->budgetReviewFields()],
                [$types['vice_mayor_approval'], $offices['vice_mayor']->id, []],
                [$types['hrmo_review'], $offices['hrmo']->id, []],
            ]);

            $offices['legislative']->update(['workflow_id' => $legislative->id]);
        }
    }

    /**
     * The offices the seeded chains point at.
     *
     * These are the only offices the seeder creates. Everything else in the
     * organization remains the Administrator's data to enter. The official
     * names come from OfficeSeeder; the short names older installs may already
     * have are matched so a re-seed never creates a second office beside them.
     *
     * @return array<string, Office>
     */
    private function supportOffices(): array
    {
        return [
            'budget' => $this->supportOffice(
                'Office of the Municipal Budget',
                'MBO',
                ['Budget Office'],
            ),
            'mayor' => $this->supportOffice(
                'Office of the Municipal Mayor',
                'MO',
                ["Mayor's Office"],
            ),
            'vice_mayor' => $this->supportOffice(
                'Office of the Vice Mayor',
                'VMAYOR',
            ),
            'hrmo' => $this->supportOffice(
                'Office of the Human Resource Management',
                'HRMO',
                ['Human Resource Management Office'],
            ),
            'legislative' => $this->supportOffice(
                'Office of the Sangguniang Bayan',
                'SBO',
                ['Legislative Office'],
            ),
        ];
    }

    /**
     * Find the office under its official name or a name an earlier install
     * used, and only create it when neither exists.
     *
     * @param  array<int, string>  $aliases
     */
    private function supportOffice(string $name, string $code, array $aliases = []): Office
    {
        foreach ([$name, ...$aliases] as $candidate) {
            $office = Office::query()->where('office_name', $candidate)->first();

            if ($office !== null) {
                return $office;
            }
        }

        return Office::firstOrCreate(
            ['office_name' => $name],
            ['office_code' => $code],
        );
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
                ['name' => 'Department Head Approval'],
                [
                    'signature_label' => 'Recommending Approval',
                    'signature_title' => 'Department Head',
                    'is_signatory' => true,
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ),
            'budget_review' => WorkflowStepType::firstOrCreate(
                ['name' => 'Budget Review'],
                [
                    'signature_label' => 'Budget Review',
                    'signature_title' => 'Budget Officer',
                    'is_signatory' => true,
                    'sort_order' => 2,
                    'is_active' => true,
                ],
            ),
            'mayor_approval' => WorkflowStepType::firstOrCreate(
                ['name' => 'Mayor Approval'],
                [
                    'signature_label' => 'Approved',
                    'signature_title' => 'City Mayor',
                    'is_signatory' => true,
                    'sort_order' => 3,
                    'is_active' => true,
                ],
            ),
            'vice_mayor_approval' => WorkflowStepType::firstOrCreate(
                ['name' => 'Vice Mayor Approval'],
                [
                    'signature_label' => 'Approved',
                    'signature_title' => 'City Vice Mayor',
                    'is_signatory' => true,
                    'sort_order' => 4,
                    'is_active' => true,
                ],
            ),
            'hrmo_review' => WorkflowStepType::firstOrCreate(
                ['name' => 'HRMO Review'],
                [
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
     * @return array<int, string>
     */
    private function departmentHeadReviewFields(): array
    {
        return [
            'purpose',
            'date_from',
            'date_to',
            'travel_classification_id',
            'origin_id',
            'destination_id',
            'official_station_id',
            'employee_id',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function budgetReviewFields(): array
    {
        return [
            'fund_source_id',
            'other_expenses',
            'expense_type_ids',
            'transportation_mode_ids',
        ];
    }

    /**
     * @param  array<int, array{0: WorkflowStepType, 1: int|null, 2: array<int, string>}>  $steps
     */
    private function addSteps(Workflow $workflow, array $steps): void
    {
        foreach ($steps as $index => [$type, $officeId, $reviewedFields]) {
            $workflow->steps()->create([
                'workflow_step_type_id' => $type->id,
                'label' => null,
                'office_id' => $officeId,
                'sort_order' => $index + 1,
                'auto_skip_creator' => true,
                'reviewed_fields' => $reviewedFields,
                'is_active' => true,
            ]);
        }
    }
}
