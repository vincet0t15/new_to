<?php

namespace App\Services;

use App\Enums\TravelOrderChangeType;
use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Exceptions\TravelOrderRoutingException;
use App\Models\OfficeDesignation;
use App\Models\TravelOrder;
use App\Models\TravelOrderDelivery;
use App\Models\User;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

/**
 * Moves a submitted travel order through its frozen route.
 *
 * A step can name a primary holder and a deputy at once. Each gets a delivery,
 * and the first to act claims the step: the actor's copy is marked acted and
 * every sibling copy is closed. The claim runs inside a transaction on the
 * order row, so two simultaneous clicks cannot both clear the same step.
 *
 * A signature step advances on approval and a receipt step advances on
 * acknowledgement. Either way the order moves to the next step that has a
 * current designation with a login, and the last step approves it. Who holds
 * a step is resolved when the order arrives, not when it was filed, so a
 * deputy covers a holder on leave and a changed designation is honoured.
 */
final class TravelOrderApprovalService
{
    public function __construct(
        private readonly WorkflowRouteResolver $routes,
        private readonly TravelOrderAudit $audit,
        private readonly TravelOrderChangeDetector $changes,
        private readonly TravelOrderNotifier $notifier,
    ) {
        //
    }

    /**
     * Stamp the order as seen by one holder without moving it.
     */
    public function receive(TravelOrder $order, User $actor): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor): TravelOrder {
            $order = $this->lock($order);

            $this->ensureSubmitted($order);

            $delivery = $order->activeDeliveryFor($actor);

            if ($delivery === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            if ($delivery->status !== TravelOrderDeliveryStatus::Received) {
                $this->claim($order, $delivery, $actor);
            }

            return $order->refresh();
        });
    }

    /**
     * Clear a signature step and send the order onward.
     */
    public function approve(TravelOrder $order, User $actor, ?string $remarks = null): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $remarks): TravelOrder {
            $order = $this->lock($order);

            $this->ensureSubmitted($order);

            $step = $this->currentStep($order);

            if ($step === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            if (! $step->stepType->is_signatory) {
                throw TravelOrderRoutingException::wrongStepType(false);
            }

            $index = (int) $order->current_step_index;

            $this->claimForAction($order, $actor);
            $this->markActed($order, $actor, 'approve');

            $this->audit->record(
                $order,
                WorkflowEvent::Approved,
                $actor,
                $step,
                $index,
                $remarks,
            );

            $this->advance($order, $index + 1, $actor);

            return $order->refresh();
        });
    }

    /**
     * Clear a receipt step and send the order onward.
     */
    public function acknowledge(TravelOrder $order, User $actor, ?string $remarks = null): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $remarks): TravelOrder {
            $order = $this->lock($order);

            $this->ensureSubmitted($order);

            $step = $this->currentStep($order);

            if ($step === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            if ($step->stepType->is_signatory) {
                throw TravelOrderRoutingException::wrongStepType(true);
            }

            $index = (int) $order->current_step_index;

            $this->claimForAction($order, $actor);
            $this->markActed($order, $actor, 'acknowledge');

            $this->audit->record(
                $order,
                WorkflowEvent::Acknowledged,
                $actor,
                $step,
                $index,
                $remarks,
            );

            $this->advance($order, $index + 1, $actor);

            return $order->refresh();
        });
    }

    /**
     * Hand the order to somebody else at the same step.
     *
     * A holder may only forward within the people the step already names.
     * Forwarding is not a way to skip a signature or choose a friendlier
     * approver; it is cover for a holder who cannot act.
     */
    public function forward(TravelOrder $order, User $actor, User $target, ?string $remarks = null): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $target, $remarks): TravelOrder {
            $order = $this->lock($order);

            $this->ensureSubmitted($order);

            $step = $this->currentStep($order);

            if ($step === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            $allowed = $this->forwardCandidates($order, $actor);

            if (! $allowed->contains(fn (User $user): bool => $user->id === $target->id)) {
                throw TravelOrderRoutingException::forwardTargetNotAllowed();
            }

            $index = (int) $order->current_step_index;

            $this->claimForAction($order, $actor);
            $this->markActed($order, $actor, 'forward');
            $this->closeOpenDeliveries($order);

            $delivery = $order->deliveries()
                ->where('step_index', $index)
                ->where('user_id', $target->id)
                ->first();

            if ($delivery instanceof TravelOrderDelivery) {
                $delivery->forceFill([
                    'status' => TravelOrderDeliveryStatus::Pending,
                    'received_at' => null,
                    'acted_at' => null,
                    'acted_via' => null,
                ])->save();
            } else {
                $order->deliveries()->create([
                    'step_index' => $index,
                    'step_type_id' => $step->workflow_step_type_id,
                    'user_id' => $target->id,
                    'status' => TravelOrderDeliveryStatus::Pending,
                ]);
            }

            $order->forceFill(['recipient_user_id' => $target->id])->save();

            $this->audit->record(
                $order,
                WorkflowEvent::Forwarded,
                $actor,
                $step,
                $index,
                $remarks,
                [
                    'from_user_id' => $actor->id,
                    'to_user_id' => $target->id,
                ],
            );

            $this->notifier->routed($order, $target, $step);

            return $order->refresh();
        });
    }

    /**
     * Send the order back to its creator and open a revision.
     *
     * The returning step is remembered before the route state is cleared, so
     * resubmission can deliver the corrected order to the same office. The
     * revision row captures the content as it was when it was sent back;
     * otherwise the creator's edits would erase the comparison baseline.
     */
    public function returnTo(TravelOrder $order, User $actor, string $remarks): TravelOrder
    {
        $remarks = trim($remarks);

        if ($remarks === '') {
            throw TravelOrderRoutingException::returnRemarksRequired();
        }

        $returned = DB::transaction(function () use ($order, $actor, $remarks): TravelOrder {
            $order = $this->lock($order);

            $this->ensureSubmitted($order);

            $step = $this->currentStep($order);

            if ($step === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            $index = (int) $order->current_step_index;
            $returnedAt = now();

            $this->claimForAction($order, $actor);
            $this->markActed($order, $actor, 'return');
            $this->closeOpenDeliveries($order);

            $revision = $order->revisions()->updateOrCreate(
                ['revision_number' => $order->revision_number],
                [
                    'returned_by_user_id' => $actor->id,
                    'returned_at' => $returnedAt,
                    'return_remarks' => $remarks,
                    'change_type' => TravelOrderChangeType::Minor,
                    'previous_data' => $this->changes->snapshot($order),
                ],
            );

            $order->forceFill([
                'status' => TravelOrderStatus::Returned,
                'current_step_index' => null,
                'recipient_user_id' => null,
                'returned_by_user_id' => $actor->id,
                'returned_from_step_index' => $index,
                'returned_from_step' => $step->displayLabel(),
                'return_remarks' => $remarks,
                'returned_at' => $returnedAt,
            ])->save();

            $this->audit->record(
                $order,
                WorkflowEvent::Returned,
                $actor,
                $step,
                $index,
                $remarks,
                null,
                $revision->id,
            );

            return $order->refresh();
        });

        $this->notifier->returned($returned, $actor);

        return $returned;
    }

    /**
     * Deliver the order at the first step from $from that has a recipient.
     *
     * Steps that cannot deliver anybody are recorded and walked past. The
     * filer is reported separately when they were among the holders but were
     * withheld, because that is a different fact from an empty step.
     *
     * Returns true when the order was delivered to a step and false when the
     * route is exhausted, in which case the order is marked approved.
     */
    public function advance(TravelOrder $order, int $from, User $actor): bool
    {
        $ids = $order->routed_steps;
        $office = $order->office;

        if (! is_array($ids) || $office === null) {
            $this->complete($order, $actor);

            return false;
        }

        $count = count($ids);

        for ($index = max(0, $from); $index < $count; $index++) {
            $step = WorkflowStep::query()
                ->with(['stepType', 'office'])
                ->find((int) $ids[$index]);

            if (! $step instanceof WorkflowStep) {
                $this->audit->record(
                    $order,
                    WorkflowEvent::StepSkipped,
                    $actor,
                    null,
                    $index,
                    'This step no longer exists in the workflow.',
                );

                continue;
            }

            $designations = $this->routes->designationsFor($step, $office);
            $recipients = $this->routes->recipientsFor($step, $office, $order->created_by);

            if ($recipients->isEmpty()) {
                $this->recordUnreachableStep($order, $actor, $step, $index, $designations);

                continue;
            }

            $this->closeOpenDeliveries($order);

            foreach ($recipients as $recipient) {
                $order->deliveries()->create([
                    'step_index' => $index,
                    'step_type_id' => $step->workflow_step_type_id,
                    'user_id' => $recipient->id,
                    'status' => TravelOrderDeliveryStatus::Pending,
                ]);
            }

            $primary = $recipients->first();

            $order->forceFill([
                'current_step_index' => $index,
                'recipient_user_id' => $primary->id,
            ])->save();

            foreach ($recipients as $recipient) {
                $this->audit->record(
                    $order,
                    WorkflowEvent::Routed,
                    $actor,
                    $step,
                    $index,
                    null,
                    ['user_id' => $recipient->id],
                );
            }

            foreach ($recipients as $recipient) {
                $this->notifier->routed($order, $recipient, $step);
            }

            return true;
        }

        $this->complete($order, $actor);

        return false;
    }

    /**
     * The step the order is waiting at, or null when it is not in the chain.
     */
    public function currentStep(TravelOrder $order): ?WorkflowStep
    {
        $ids = $order->routed_steps;
        $index = $order->current_step_index;

        if ($index === null || ! is_array($ids) || ! isset($ids[$index])) {
            return null;
        }

        return WorkflowStep::query()
            ->with(['stepType', 'office'])
            ->find((int) $ids[$index]);
    }

    /**
     * The people this holder may forward the order to at the current step.
     *
     * @return BaseCollection<int, User>
     */
    public function forwardCandidates(TravelOrder $order, User $actor): BaseCollection
    {
        $step = $this->currentStep($order);
        $office = $order->office;

        if (
            $step === null
            || $office === null
            || $order->status !== TravelOrderStatus::Submitted
        ) {
            return new Collection;
        }

        return $this->routes
            ->recipientsFor($step, $office, $order->created_by)
            ->reject(fn (User $user): bool => $user->id === $actor->id)
            ->values();
    }

    /**
     * Give one holder the step and close everybody else's copy.
     */
    private function claim(TravelOrder $order, TravelOrderDelivery $delivery, User $actor): void
    {
        $delivery->forceFill([
            'status' => TravelOrderDeliveryStatus::Received,
            'received_at' => now(),
        ])->save();

        $order->deliveries()
            ->where('step_index', $order->current_step_index)
            ->whereKeyNot($delivery->getKey())
            ->open()
            ->update([
                'status' => TravelOrderDeliveryStatus::Closed->value,
                'updated_at' => now(),
            ]);

        $order->forceFill(['recipient_user_id' => $actor->id])->save();

        $this->audit->record(
            $order,
            WorkflowEvent::Received,
            $actor,
            $this->currentStep($order),
            $order->current_step_index,
        );
    }

    /**
     * Claim a pending copy before acting on it.
     */
    private function claimForAction(TravelOrder $order, User $actor): void
    {
        $delivery = $order->activeDeliveryFor($actor);

        if ($delivery === null) {
            throw TravelOrderRoutingException::staleAction();
        }

        if ($delivery->status === TravelOrderDeliveryStatus::Pending) {
            $this->claim($order, $delivery, $actor);
        }
    }

    /**
     * Record that this holder cleared the step.
     */
    private function markActed(TravelOrder $order, User $actor, string $via): void
    {
        $order->deliveries()
            ->where('step_index', $order->current_step_index)
            ->where('user_id', $actor->id)
            ->open()
            ->update([
                'status' => TravelOrderDeliveryStatus::Acted->value,
                'acted_at' => now(),
                'acted_via' => $via,
                'updated_at' => now(),
            ]);
    }

    /**
     * Record why a step had nobody to deliver to.
     *
     * @param  Collection<int, OfficeDesignation>  $designations
     */
    private function recordUnreachableStep(
        TravelOrder $order,
        User $actor,
        WorkflowStep $step,
        int $index,
        Collection $designations,
    ): void {
        $creatorIsHolder = $designations->contains(
            fn (OfficeDesignation $designation): bool => $designation->employee->user?->id === $order->created_by,
        );

        if ($step->auto_skip_creator && $creatorIsHolder) {
            $this->audit->record(
                $order,
                WorkflowEvent::FilerWithheld,
                $actor,
                $step,
                $index,
                'The filer also holds this step, so it was withheld from them.',
            );

            return;
        }

        $this->audit->record(
            $order,
            WorkflowEvent::StepSkipped,
            $actor,
            $step,
            $index,
            $designations->isEmpty()
                ? 'No active designation is configured for this step.'
                : 'No designated employee has a login account.',
        );
    }

    private function complete(TravelOrder $order, User $actor): void
    {
        $this->closeOpenDeliveries($order);

        $order->forceFill([
            'status' => TravelOrderStatus::Approved,
            'current_step_index' => null,
            'recipient_user_id' => null,
        ])->save();

        $this->audit->record($order, WorkflowEvent::Completed, $actor);
    }

    private function ensureSubmitted(TravelOrder $order): void
    {
        if ($order->status !== TravelOrderStatus::Submitted) {
            throw TravelOrderRoutingException::staleAction();
        }
    }

    private function closeOpenDeliveries(TravelOrder $order): void
    {
        $order->deliveries()->open()->update([
            'status' => TravelOrderDeliveryStatus::Closed->value,
            'updated_at' => now(),
        ]);
    }

    /**
     * Lock the order row for the duration of a claim or move.
     */
    private function lock(TravelOrder $order): TravelOrder
    {
        return TravelOrder::query()
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }
}
