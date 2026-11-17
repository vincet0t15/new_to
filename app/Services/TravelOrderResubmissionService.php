<?php

namespace App\Services;

use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Exceptions\TravelOrderRoutingException;
use App\Models\TravelOrder;
use App\Models\TravelOrderRevision;
use App\Models\User;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\DB;

/**
 * Files a returned travel order back into its frozen route.
 *
 * The order number and the captured chain never change. What changes is the
 * revision: the return row is completed with the creator's remarks and the
 * fields that moved, then the next revision is delivered to the returning
 * step using the designations that exist now.
 */
final class TravelOrderResubmissionService
{
    public function __construct(
        private readonly TravelOrderChangeDetector $changes,
        private readonly TravelOrderApprovalService $approvals,
        private readonly TravelOrderAudit $audit,
    ) {
        //
    }

    public function resubmit(TravelOrder $order, User $actor, string $remarks): TravelOrder
    {
        $remarks = trim($remarks);

        if ($remarks === '') {
            throw TravelOrderRoutingException::resubmissionRemarksRequired();
        }

        return DB::transaction(function () use ($order, $actor, $remarks): TravelOrder {
            $order = $this->lock($order);

            if (! $order->isOwnedBy($actor)) {
                throw TravelOrderRoutingException::onlyCreatorCanResubmit();
            }

            if ($order->status !== TravelOrderStatus::Returned) {
                throw TravelOrderRoutingException::notReturned();
            }

            $revision = $this->revisionFor($order);
            $previous = is_array($revision->previous_data) ? $revision->previous_data : [];
            $changed = $this->changes->changedFields($previous, $order);
            $changeType = $this->changes->changeType($changed);
            $resumeIndex = $this->changes->resumeIndex(
                $order,
                $changeType,
                array_keys($changed),
            );

            $revision->forceFill([
                'revised_by_user_id' => $actor->id,
                'revision_remarks' => $remarks,
                'change_type' => $changeType,
                'changed_fields' => array_keys($changed),
                'previous_data' => array_intersect_key($previous, $changed),
                'new_data' => $changed,
                'resubmitted_at' => now(),
            ])->save();

            $previousRevision = (int) $order->revision_number;

            $order->forceFill([
                'status' => TravelOrderStatus::Submitted,
                'current_step_index' => null,
                'recipient_user_id' => null,
                'revision_number' => $previousRevision + 1,
                'last_resubmitted_at' => now(),
            ])->save();

            $this->audit->record(
                $order,
                WorkflowEvent::Resubmitted,
                $actor,
                $this->stepAt($order, $resumeIndex),
                $resumeIndex,
                $remarks,
                [
                    'previous_revision' => $previousRevision,
                    'change_type' => $changeType->value,
                    'changed_fields' => array_keys($changed),
                    'resume_index' => $resumeIndex,
                    'policy' => config('travel-orders.resubmission.policy', 'resume'),
                ],
                $revision->id,
            );

            // advance() resolves the holders again, so a designation changed
            // during the revision is honoured, and it records Routed for each.
            if (! $this->approvals->advance($order, $resumeIndex, $actor)) {
                throw TravelOrderRoutingException::noRecipientLeft();
            }

            return $order->refresh();
        });
    }

    /**
     * The open revision row, or a legacy row for an order returned before
     * revisions existed.
     */
    private function revisionFor(TravelOrder $order): TravelOrderRevision
    {
        $revision = $order->revisions()
            ->where('revision_number', $order->revision_number)
            ->first();

        if ($revision instanceof TravelOrderRevision) {
            return $revision;
        }

        return $order->revisions()->create([
            'revision_number' => $order->revision_number,
            'returned_by_user_id' => $order->returned_by_user_id,
            'returned_at' => $order->returned_at ?? now(),
            'return_remarks' => $order->return_remarks,
            'previous_data' => $this->changes->snapshot($order),
            'change_type' => 'minor',
        ]);
    }

    private function stepAt(TravelOrder $order, int $index): ?WorkflowStep
    {
        $ids = $order->routed_steps;

        if (! is_array($ids) || ! isset($ids[$index])) {
            return null;
        }

        return WorkflowStep::query()
            ->with(['stepType', 'office'])
            ->find((int) $ids[$index]);
    }

    /**
     * Lock the order row for the duration of the resubmission.
     */
    private function lock(TravelOrder $order): TravelOrder
    {
        return TravelOrder::query()
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }
}
