<?php

namespace App\Services;

use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Exceptions\TravelOrderRoutingException;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\DB;

/**
 * Files a draft travel order into its approval chain.
 *
 * Submission is the only moment the route is decided. The active steps of the
 * office's workflow are frozen onto the order as ids, so an administrator
 * editing the workflow later cannot move a document that is already
 * travelling. Every step is checked before anything is written: a chain that
 * would stall at a later office is refused now, with the name of the step that
 * is not ready, rather than stranding the document halfway through.
 */
final class TravelOrderSubmissionService
{
    public function __construct(
        private readonly WorkflowRouteResolver $routes,
        private readonly TravelOrderApprovalService $approvals,
        private readonly TravelOrderAudit $audit,
    ) {
        //
    }

    public function submit(TravelOrder $order, User $actor): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor): TravelOrder {
            $order = $this->lock($order);

            if (! $order->isOwnedBy($actor)) {
                throw TravelOrderRoutingException::notOwner();
            }

            if ($order->status !== TravelOrderStatus::Draft) {
                throw TravelOrderRoutingException::notDraft();
            }

            $office = $order->office;

            if ($office === null) {
                throw TravelOrderRoutingException::noOffice();
            }

            $workflow = $this->routes->workflowFor($office);

            if ($workflow === null) {
                throw TravelOrderRoutingException::noWorkflow();
            }

            $steps = $this->routes->stepsFor($workflow);

            if ($steps->isEmpty()) {
                throw TravelOrderRoutingException::noSteps();
            }

            $this->assertStepsCanReceive($steps->all(), $office, $actor);

            $routedSteps = $steps
                ->pluck('id')
                ->map(fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $order->forceFill([
                'workflow_id' => $workflow->id,
                'status' => TravelOrderStatus::Submitted,
                'routed_steps' => $routedSteps,
                'current_step_index' => null,
                'recipient_user_id' => null,
                'submitted_at' => now(),
            ])->save();

            $this->audit->record($order, WorkflowEvent::Submitted, $actor);

            // The first step is delivered now. advance() walks past steps the
            // filer is withheld from, so a head who files their own order does
            // not approve it themselves.
            if (! $this->approvals->advance($order, 0, $actor)) {
                throw TravelOrderRoutingException::noRecipientLeft();
            }

            $order->refresh();

            return $order;
        });
    }

    /**
     * Refuse a chain that cannot deliver any of its steps.
     *
     * The check is deliberately about people, not configuration: a step with
     * no designation, or one whose designee has no login, cannot receive a
     * copy. Catching it here keeps the failure at the filer's desk.
     *
     * @param  array<int, WorkflowStep>  $steps
     */
    private function assertStepsCanReceive(array $steps, Office $office, User $actor): void
    {
        $deliverable = false;

        foreach ($steps as $step) {
            $label = $step->displayLabel();
            $designations = $this->routes->designationsFor($step, $office);

            if ($designations->isEmpty()) {
                throw TravelOrderRoutingException::stepNotReady(
                    $label,
                    'no active designation is configured for this office and step.',
                );
            }

            $withAccounts = $designations->filter(
                fn (OfficeDesignation $designation): bool => $designation->employee->user !== null,
            );

            if ($withAccounts->isEmpty()) {
                throw TravelOrderRoutingException::stepNotReady(
                    $label,
                    'the designated employee has no login account.',
                );
            }

            if ($this->routes->recipientsFor($step, $office, $actor->id)->isNotEmpty()) {
                $deliverable = true;
            }
        }

        if (! $deliverable) {
            throw TravelOrderRoutingException::noRecipientLeft();
        }
    }

    /**
     * Lock the row for the duration of the submission.
     */
    private function lock(TravelOrder $order): TravelOrder
    {
        return TravelOrder::query()
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }
}
