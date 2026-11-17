<?php

namespace App\Services;

use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Resolves the approval chain an office's travel orders follow.
 *
 * The resolution has two layers, matching the locked routing decision:
 *
 *  1. Which workflow applies — the office's own chain when it has one, the
 *     default chain otherwise.
 *  2. Who answers each step — the current designations for the target office
 *     and step type. A step without a target office follows the filing office,
 *     which is how the Department Head step binds to the filer's own office.
 *
 * Names are resolved at arrival, not at filing, so a leave of absence or a
 * change of designation reaches the person actually holding the seat. What is
 * frozen at submission is the list of step ids, which Phase 2 stores on the
 * travel order.
 */
final class WorkflowRouteResolver
{
    /**
     * The workflow used by an office, or null when nothing is configured.
     */
    public function workflowFor(?Office $office): ?Workflow
    {
        if ($office?->workflow_id !== null) {
            $assigned = $office->workflow()->first();

            if ($assigned instanceof Workflow && $assigned->is_active) {
                return $assigned;
            }
        }

        return Workflow::query()
            ->active()
            ->default()
            ->orderBy('id')
            ->first();
    }

    /**
     * Why an office is not using the workflow assigned to it, if it is not.
     */
    public function fallbackReason(?Office $office): ?string
    {
        if ($office?->workflow_id === null) {
            return null;
        }

        $assigned = $office->workflow()->first();

        if ($assigned === null) {
            return 'The assigned workflow no longer exists, so the default chain applies.';
        }

        if (! $assigned->is_active) {
            return 'The assigned workflow is inactive, so the default chain applies.';
        }

        return null;
    }

    /**
     * The office that answers a step for a given filing office.
     *
     * A step that names no office follows the filing office, which is how the
     * single Department Head step becomes every department's head.
     */
    public function targetOfficeFor(WorkflowStep $step, Office $filingOffice): Office
    {
        return $step->office ?? $filingOffice;
    }

    /**
     * The active designations that answer a step for a filing office.
     *
     * The employee and login are eager-loaded because the routing engine needs
     * to know not only who holds the seat but whether that person can receive
     * a copy.
     *
     * @return Collection<int, OfficeDesignation>
     */
    public function designationsFor(WorkflowStep $step, Office $filingOffice): Collection
    {
        $targetOffice = $this->targetOfficeFor($step, $filingOffice);

        return OfficeDesignation::query()
            ->current()
            ->where('office_id', $targetOffice->id)
            ->where('workflow_step_type_id', $step->workflow_step_type_id)
            ->with(['employee.user:id,employee_id'])
            ->orderBy('is_deputy')
            ->orderBy('id')
            ->get();
    }

    /**
     * The login accounts that should receive a step.
     *
     * Every current holder and deputy gets a copy. A step that opts to skip
     * its creator drops that account when it is among the holders, because a
     * travel order must not approve itself.
     *
     * @return BaseCollection<int, User>
     */
    public function recipientsFor(WorkflowStep $step, Office $filingOffice, ?int $excludeUserId = null): BaseCollection
    {
        $recipients = $this->designationsFor($step, $filingOffice)
            ->map(fn (OfficeDesignation $designation): ?User => $designation->employee->user)
            ->filter(fn (?User $user): bool => $user instanceof User)
            ->unique('id')
            ->values();

        if ($excludeUserId !== null && $step->auto_skip_creator) {
            $recipients = $recipients
                ->reject(fn (User $user): bool => $user->id === $excludeUserId)
                ->values();
        }

        return $recipients;
    }

    /**
     * The active steps of a workflow, in administrator-defined order.
     *
     * @return Collection<int, WorkflowStep>
     */
    public function stepsFor(Workflow $workflow): Collection
    {
        return $workflow->steps()
            ->active()
            ->with(['stepType', 'office'])
            ->get();
    }

    /**
     * The active steps that apply to an office, or an empty collection.
     *
     * @return Collection<int, WorkflowStep>
     */
    public function stepsForOffice(?Office $office): Collection
    {
        $workflow = $this->workflowFor($office);

        if ($workflow === null) {
            return new Collection;
        }

        return $this->stepsFor($workflow);
    }

    /**
     * The chain an office would follow, with its current designees.
     *
     * This is the payload behind the Administrator's route preview: it is
     * read-only and never decides anything by itself, so it can safely expose
     * the holder names that the engine will target.
     *
     * @return array{
     *     office: array{id: int, office_name: string},
     *     workflow: array{id: int, name: string, slug: string, is_default: bool, is_active: bool}|null,
     *     uses_override: bool,
     *     fallback_reason: string|null,
     *     warning: string|null,
     *     is_ready: bool,
     *     steps: array<int, array{
     *         id: int,
     *         label: string,
     *         step_type: array{id: int, name: string, is_signatory: bool},
     *         office: array{id: int, office_name: string},
     *         uses_filing_office: bool,
     *         auto_skip_creator: bool,
     *         is_ready: bool,
     *         warning: string|null,
     *         designees: array<int, array{id: int, employee_id: int, name: string, is_deputy: bool, has_account: bool}>
     *     }>
     * }
     */
    public function preview(Office $office): array
    {
        $workflow = $this->workflowFor($office);
        $steps = $workflow === null ? new Collection : $this->stepsFor($workflow);

        $previewSteps = $steps
            ->map(fn (WorkflowStep $step): array => $this->previewStep($step, $office))
            ->values()
            ->all();

        $isReady = $previewSteps !== []
            && collect($previewSteps)->every(fn (array $step): bool => $step['is_ready']);

        $warning = null;

        if ($workflow === null) {
            $warning = 'No active default workflow is configured.';
        } elseif ($previewSteps === []) {
            $warning = 'The resolved workflow has no active steps.';
        }

        return [
            'office' => [
                'id' => $office->id,
                'office_name' => $office->office_name,
            ],
            'workflow' => $workflow === null ? null : [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'slug' => $workflow->slug,
                'is_default' => $workflow->is_default,
                'is_active' => $workflow->is_active,
            ],
            'uses_override' => $workflow !== null
                && $office->workflow_id !== null
                && ! $workflow->is_default,
            'fallback_reason' => $this->fallbackReason($office),
            'warning' => $warning,
            'is_ready' => $isReady,
            'steps' => $previewSteps,
        ];
    }

    /**
     * One step of the preview, with the people who answer it.
     *
     * @return array{
     *     id: int,
     *     label: string,
     *     step_type: array{id: int, name: string, is_signatory: bool},
     *     office: array{id: int, office_name: string},
     *     uses_filing_office: bool,
     *     auto_skip_creator: bool,
     *     is_ready: bool,
     *     warning: string|null,
     *     designees: array<int, array{id: int, employee_id: int, name: string, is_deputy: bool, has_account: bool}>
     * }
     */
    private function previewStep(WorkflowStep $step, Office $filingOffice): array
    {
        $targetOffice = $this->targetOfficeFor($step, $filingOffice);

        $designations = $this->designationsFor($step, $filingOffice);

        $designees = $designations
            ->map(fn (OfficeDesignation $designation): array => [
                'id' => $designation->id,
                'employee_id' => $designation->employee->id,
                'name' => $designation->employee->full_name,
                'is_deputy' => $designation->is_deputy,
                'has_account' => $designation->employee->user !== null,
            ])
            ->values()
            ->all();

        $isReady = collect($designees)->contains(
            fn (array $designee): bool => $designee['has_account'],
        );

        $warning = null;

        if ($designations->isEmpty()) {
            $warning = 'No active designation is configured for this office and step.';
        } elseif (! $isReady) {
            $warning = 'The designated employee has no login account, so the travel order cannot be delivered.';
        }

        return [
            'id' => $step->id,
            'label' => $step->displayLabel(),
            'step_type' => [
                'id' => $step->stepType->id,
                'name' => $step->stepType->name,
                'is_signatory' => $step->stepType->is_signatory,
            ],
            'office' => [
                'id' => $targetOffice->id,
                'office_name' => $targetOffice->office_name,
            ],
            'uses_filing_office' => $step->followsFilingOffice(),
            'auto_skip_creator' => $step->auto_skip_creator,
            'is_ready' => $isReady,
            'warning' => $warning,
            'designees' => $designees,
        ];
    }
}
