<?php

namespace App\Services;

use App\Enums\SystemRole;
use App\Enums\TravelOrderChangeType;
use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Exceptions\TravelOrderRoutingException;
use App\Models\FundSource;
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
        private readonly TravelOrderNumberService $numbers,
    ) {
        //
    }

    /**
     * Record that one holder has seen the order without claiming the step.
     *
     * Receiving is a read receipt, not an action: the other open copies stay
     * actionable so a deputy opening the order does not lock the primary out.
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

            if ($delivery->status === TravelOrderDeliveryStatus::Pending) {
                $delivery->forceFill([
                    'status' => TravelOrderDeliveryStatus::Received,
                    'received_at' => now(),
                ])->save();

                $this->audit->record(
                    $order,
                    WorkflowEvent::Received,
                    $actor,
                    $this->currentStep($order),
                    $order->current_step_index,
                );
            }

            return $order->refresh();
        });
    }

    /**
     * Record Budget office earmark/fund allocation details.
     *
     * @param  array{earmark_number?: string|null, remarks?: string|null}  $data
     */
    public function earmark(TravelOrder $order, User $actor, array $data = []): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $data): TravelOrder {
            $order = $this->lock($order);
            $this->ensureSubmitted($order);
            $step = $this->currentStep($order);

            if ($step === null || $order->activeDeliveryFor($actor) === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            $this->ensureBudgetStepAction($actor, $step, 'travel-orders.earmark');

            $index = (int) $order->current_step_index;
            $remarks = $this->nullableRemarks($data['remarks'] ?? null);
            $earmarkNumber = $this->nullableRemarks($data['earmark_number'] ?? null);

            $order->forceFill([
                'earmarked_by_user_id' => $actor->id,
                'earmarked_at' => now(),
                'earmark_number' => $earmarkNumber ?? $order->earmark_number,
                'earmark_remarks' => $remarks,
            ])->save();

            $this->audit->record(
                $order,
                WorkflowEvent::Earmarked,
                $actor,
                $step,
                $index,
                $remarks,
                [
                    'earmark_number' => $order->earmark_number,
                ],
            );

            return $order->refresh();
        });
    }

    /**
     * Assign the fund source for a submitted travel order.
     *
     * Budget owns fund-source selection; this is an auditable holder action and
     * does not advance the workflow.
     */
    public function assignFundSource(TravelOrder $order, User $actor, ?int $fundSourceId, ?string $remarks = null): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $fundSourceId, $remarks): TravelOrder {
            $order = $this->lock($order);
            $this->ensureSubmitted($order);
            $step = $this->currentStep($order);

            if ($step === null || $order->activeDeliveryFor($actor) === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            $this->ensureBudgetStepAction($actor, $step, 'travel-orders.assign-fund-source');

            $fundSource = $fundSourceId === null
                ? null
                : FundSource::query()->find($fundSourceId);

            if ($fundSourceId !== null && $fundSource === null) {
                throw new \InvalidArgumentException('Selected fund source was not found.');
            }

            $index = (int) $order->current_step_index;
            $remarks = $this->nullableRemarks($remarks);

            $order->forceFill([
                'fund_source_id' => $fundSource?->id,
                'fund_source' => $fundSource?->name,
                'fund_source_assigned_by_user_id' => $actor->id,
                'fund_source_assigned_at' => now(),
            ])->save();

            $this->audit->record(
                $order,
                WorkflowEvent::FundSourceAssigned,
                $actor,
                $step,
                $index,
                $remarks,
                [
                    'fund_source_id' => $order->fund_source_id,
                    'fund_source' => $order->fund_source,
                ],
            );

            return $order->refresh();
        });
    }

    /**
     * Record the HRMO release details.
     *
     * The filing and series numbers are owned by the numbering service: the
     * filing number arrives at submission and the series number when HRMO
     * clears its step. Recording cannot rewrite either one.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(TravelOrder $order, User $actor, array $data): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $data): TravelOrder {
            $order = $this->lock($order);
            $this->ensureSubmitted($order);
            $step = $this->currentStep($order);

            if ($step === null || $order->activeDeliveryFor($actor) === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            $index = (int) $order->current_step_index;
            $remarks = $this->nullableRemarks($data['remarks'] ?? null);

            $order->forceFill([
                'recorded_by_user_id' => $actor->id,
                'recorded_at' => now(),
                'recording_remarks' => $remarks,
                'distributed_at' => ($data['distributed'] ?? false) ? now() : $order->distributed_at,
            ])->save();

            $this->audit->record(
                $order,
                WorkflowEvent::Recorded,
                $actor,
                $step,
                $index,
                $remarks,
                [
                    'control_no' => $order->control_no,
                    'to_number' => $order->to_number,
                    'distributed' => $order->distributed_at !== null,
                ],
            );

            return $order->refresh();
        });
    }

    /**
     * Clear a signature step and send the order onward.
     */
    public function approve(TravelOrder $order, User $actor, ?string $remarks = null, bool $isInternal = false): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $remarks, $isInternal): TravelOrder {
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

            $series = $this->assignHrmoSeries($order, $step);

            $this->audit->record(
                $order,
                WorkflowEvent::Approved,
                $actor,
                $step,
                $index,
                $remarks,
                $series === null ? null : ['control_no' => $series],
                null,
                $isInternal,
            );

            $this->advance($order, $index + 1, $actor);

            return $order->refresh();
        });
    }

    /**
     * Clear a receipt step and send the order onward.
     */
    public function acknowledge(TravelOrder $order, User $actor, ?string $remarks = null, bool $isInternal = false): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $remarks, $isInternal): TravelOrder {
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

            $series = $this->assignHrmoSeries($order, $step);

            $this->audit->record(
                $order,
                WorkflowEvent::Acknowledged,
                $actor,
                $step,
                $index,
                $remarks,
                $series === null ? null : ['control_no' => $series],
                null,
                $isInternal,
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
    public function forward(TravelOrder $order, User $actor, User $target, ?string $remarks = null, bool $isInternal = false): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $target, $remarks, $isInternal): TravelOrder {
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

            $order->deliveries()->create([
                'step_index' => $index,
                'step_type_id' => $step->workflow_step_type_id,
                'user_id' => $target->id,
                'status' => TravelOrderDeliveryStatus::Pending,
            ]);

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
                null,
                $isInternal,
            );

            $this->notifier->routed($order, $target, $step);

            return $order->refresh();
        });
    }

    /**
     * Re-route the open delivery at the current step to a new holder.
     *
     * Used by administrators to rescue a document whose original holder can no
     * longer act (changed designation, departed staff). The document does not
     * advance: it stays at the same step, only the person holding it changes.
     * The audit trail records who reassigned it and from whom.
     */
    public function reassignCurrentStep(TravelOrder $order, User $actor, User $target, ?string $remarks = null): TravelOrder
    {
        return DB::transaction(function () use ($order, $actor, $target, $remarks): TravelOrder {
            $order = $this->lock($order);

            $this->ensureSubmitted($order);

            $step = $this->currentStep($order);

            if ($step === null) {
                throw TravelOrderRoutingException::staleAction();
            }

            $index = (int) $order->current_step_index;

            $fromUserId = $order->recipient_user_id;

            $this->closeOpenDeliveries($order);

            $order->deliveries()->create([
                'step_index' => $index,
                'step_type_id' => $step->workflow_step_type_id,
                'user_id' => $target->id,
                'status' => TravelOrderDeliveryStatus::Pending,
            ]);

            $order->forceFill(['recipient_user_id' => $target->id])->save();

            $this->audit->record(
                $order,
                WorkflowEvent::Reassigned,
                $actor,
                $step,
                $index,
                $remarks,
                [
                    'from_user_id' => $fromUserId,
                    'to_user_id' => $target->id,
                ],
            );

            $this->notifier->routed($order, $target, $step);

            return $order->refresh();
        });
    }

    /**
     * The people an administrator may reassign the current step to.
     *
     * Prefers the holders the step currently names (so a reassignment follows
     * the configured workflow). When none are configured, falls back to every
     * account that holds an approver role, so a stranded document can still be
     * rescued instead of being stuck forever.
     *
     * @return BaseCollection<int, User>
     */
    public function reassignCandidates(TravelOrder $order): BaseCollection
    {
        $step = $this->currentStep($order);

        if ($step === null || $order->office === null || $order->status !== TravelOrderStatus::Submitted) {
            return new Collection;
        }

        $candidateIds = $this->routes->recipientsFor($step, $order->office, $order->created_by)->pluck('id');

        if ($candidateIds->isEmpty()) {
            $candidateIds = User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('slug', [
                    SystemRole::DepartmentHead->value,
                    SystemRole::BudgetOfficer->value,
                    SystemRole::Mayor->value,
                    SystemRole::ViceMayor->value,
                    SystemRole::Hrmo->value,
                    SystemRole::Admin->value,
                    SystemRole::SuperAdmin->value,
                ]))
                ->orderBy('name')
                ->pluck('id');
        }

        return User::query()
            ->whereIn('id', $candidateIds)
            ->with('roles')
            ->orderBy('name')
            ->get();
    }

    /**
     * In-flight orders a designation change would leave with the old holder.
     *
     * Only the current step matters: an open delivery is the authority for who
     * can act now, and changing a designation never moves a document by itself.
     * The candidate list helps an admin repair those deliveries in one pass.
     *
     * @return array{designation: array<string, mixed>, orders: array<int, array<string, mixed>>, candidates: array<int, array{id: int, name: string, username: string}>}
     */
    public function designationImpact(OfficeDesignation $designation): array
    {
        $designation->loadMissing(['office', 'stepType', 'employee.user']);

        $outgoingUserId = $designation->employee->user?->id;

        $orders = $outgoingUserId === null
            ? new Collection
            : TravelOrder::query()
                ->where('status', TravelOrderStatus::Submitted)
                ->where('recipient_user_id', $outgoingUserId)
                ->whereHas('deliveries', fn ($query) => $query
                    ->open()
                    ->whereColumn(
                        'travel_order_deliveries.step_index',
                        'travel_orders.current_step_index',
                    ))
                ->with(['office:id,office_name', 'recipient:id,name'])
                ->orderBy('submitted_at')
                ->get()
                ->filter(function (TravelOrder $order) use ($designation): bool {
                    $step = $this->currentStep($order);

                    return $step !== null
                        && $step->workflow_step_type_id === $designation->workflow_step_type_id;
                })
                ->values();

        $firstOrder = $orders->first();
        $candidates = $firstOrder instanceof TravelOrder
            ? $this->reassignCandidates($firstOrder)
                ->reject(fn (User $user): bool => $outgoingUserId !== null && $user->id === $outgoingUserId)
                ->values()
            : new BaseCollection;

        return [
            'designation' => [
                'id' => $designation->id,
                'office' => $designation->office->office_name,
                'step' => $designation->stepType->name,
                'holder' => $designation->employee->full_name,
            ],
            'orders' => $orders->map(function (TravelOrder $order): array {
                $step = $this->currentStep($order);

                return [
                    'id' => $order->id,
                    'control_no' => $order->control_no,
                    'to_number' => $order->to_number,
                    'purpose' => $order->purpose,
                    'age_days' => (int) abs($order->submitted_at?->diffInDays(now()) ?? 0),
                    'holder' => $order->recipient_user_id !== null
                        ? $order->recipient->name
                        : '—',
                    'step' => $step?->displayLabel() ?? '—',
                ];
            })->all(),
            'candidates' => $candidates->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ])->all(),
        ];
    }

    /**
     * Reassign several orders, reporting each refusal without losing the rest.
     *
     * Each order keeps its own transaction through reassignCurrentStep(), so a
     * stale document failing in the middle does not roll back earlier rescues.
     *
     * @param  iterable<TravelOrder>  $orders
     * @return array{reassigned: array<int, int>, failed: array<int, array{id: int, message: string}>}
     */
    public function reassignMany(iterable $orders, User $actor, User $target, ?string $remarks = null): array
    {
        $reassigned = [];
        $failed = [];

        foreach ($orders as $order) {
            try {
                $this->reassignCurrentStep($order, $actor, $target, $remarks);
                $reassigned[] = $order->id;
            } catch (TravelOrderRoutingException $exception) {
                $failed[] = [
                    'id' => $order->id,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return ['reassigned' => $reassigned, 'failed' => $failed];
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
                throw TravelOrderRoutingException::routeStepMissing($index + 1);
            }

            $designations = $this->routes->designationsFor($step, $office);
            $recipients = $this->routes->recipientsFor($step, $office, $order->created_by);

            if ($recipients->isEmpty()) {
                $this->assertDeliverableOrSkippable($order, $step, $designations);

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
     * Fail closed when a required step cannot be delivered.
     *
     * @param  Collection<int, OfficeDesignation>  $designations
     */
    private function assertDeliverableOrSkippable(
        TravelOrder $order,
        WorkflowStep $step,
        Collection $designations,
    ): void {
        $label = $step->displayLabel();
        $creatorIsHolder = $designations->contains(
            fn (OfficeDesignation $designation): bool => $designation->employee->user?->id === $order->created_by,
        );

        if ($step->auto_skip_creator && $creatorIsHolder && $designations->count() === 1) {
            return;
        }

        if ($designations->isEmpty()) {
            throw TravelOrderRoutingException::stepUndeliverable(
                $label,
                'no active designation is configured for this office and step.',
            );
        }

        throw TravelOrderRoutingException::stepUndeliverable(
            $label,
            $creatorIsHolder && $step->auto_skip_creator
                ? 'the filer holds this step and no other designated employee has a login account.'
                : 'no designated employee has a login account.',
        );
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

    /**
     * Give the order its HRMO series number when HRMO clears its step.
     *
     * Legacy orders filed before automatic numbering may have no filing
     * number, so one is filled here too. A number already on the order is
     * never overwritten.
     */
    private function assignHrmoSeries(TravelOrder $order, WorkflowStep $step): ?string
    {
        if (! $this->isHrmoStep($step)) {
            return null;
        }

        $changes = [];

        if (blank($order->to_number)) {
            $submittedAt = $order->submitted_at;

            $changes['to_number'] = $this->numbers->nextSubmissionNumber(
                $submittedAt instanceof \DateTimeInterface
                    ? (int) $submittedAt->format('Y')
                    : (int) now()->year,
            );
        }

        if (blank($order->control_no)) {
            $changes['control_no'] = $this->numbers->nextSeriesNumber();
        }

        if ($changes === []) {
            return null;
        }

        $order->forceFill($changes)->save();

        return $changes['control_no'] ?? null;
    }

    private function isHrmoStep(WorkflowStep $step): bool
    {
        return in_array($step->stepType->name, ['HRMO Review', 'HRMO'], true);
    }

    private function ensureBudgetStepAction(User $actor, WorkflowStep $step, string $permission): void
    {
        $name = $step->stepType->name;
        $isBudgetStep = in_array($name, ['Budget Review', 'Budget'], true);

        if (! $isBudgetStep || ! $actor->can($permission)) {
            throw TravelOrderRoutingException::budgetActionNotAllowed();
        }
    }

    private function nullableRemarks(?string $remarks): ?string
    {
        $remarks = trim((string) $remarks);

        return $remarks === '' ? null : $remarks;
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
