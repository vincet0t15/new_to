<?php

namespace App\Services;

use App\Enums\TravelOrderStatus;
use App\Enums\WorkflowEvent;
use App\Models\TravelOrder;
use App\Models\TravelOrderAction;
use App\Models\TravelOrderDelivery;
use App\Models\TravelOrderRevision;
use App\Models\WorkflowStep;

/**
 * Turns the stored routing state into the arrays the screens render.
 *
 * A submitted order is described by its frozen step ids; a draft is described
 * by the route it would take. Keeping that difference here means the employee
 * and approver screens show the same progress timeline without either
 * controller re-implementing the routing rules.
 */
final class TravelOrderRoutePresenter
{
    public function __construct(
        private readonly WorkflowRouteResolver $routes,
    ) {
        //
    }

    /**
     * The ordered route with each step's progress and current holders.
     *
     * @return array<int, array{
     *     index: int,
     *     label: string,
     *     office: string|null,
     *     is_signatory: bool,
     *     status: string,
     *     holders: array<int, array{id: int, name: string, status: string}>
     * }>
     */
    public function route(TravelOrder $order): array
    {
        $order->loadMissing([
            'office:id,office_name',
            'deliveries.user:id,name',
            'actions',
        ]);

        $steps = $this->stepsFor($order);

        if ($steps === []) {
            return [];
        }

        $currentIndex = $order->current_step_index;
        $skipped = $order->actions
            ->where('event', WorkflowEvent::StepSkipped)
            ->pluck('step_index')
            ->filter(fn (mixed $index): bool => $index !== null)
            ->map(fn (mixed $index): int => (int) $index)
            ->all();

        $returnedIndex = $order->status === TravelOrderStatus::Returned
            ? $this->returnedIndex($order)
            : null;

        return array_map(function (array $entry) use ($order, $currentIndex, $skipped, $returnedIndex): array {
            /** @var WorkflowStep|null $step */
            $step = $entry['step'];
            $index = $entry['index'];

            $status = $this->statusFor(
                $order,
                $index,
                $currentIndex,
                $returnedIndex,
                in_array($index, $skipped, true),
            );

            return [
                'index' => $index,
                'label' => $step?->displayLabel() ?? 'Removed step',
                'office' => $step?->office->office_name
                    ?? $order->office?->office_name,
                'is_signatory' => $step?->stepType->is_signatory ?? false,
                'status' => $status,
                'holders' => $this->holdersFor($order, $index, $status),
            ];
        }, $steps);
    }

    /**
     * The immutable audit trail, oldest first.
     *
     * @return array<int, array{
     *     id: int,
     *     event: string,
     *     event_label: string,
     *     step_label: string|null,
     *     step_index: int|null,
     *     actor: string|null,
     *     remarks: string|null,
     *     created_at: string|null
     * }>
     */
    public function timeline(TravelOrder $order, bool $includeInternal = true): array
    {
        $actions = $order->relationLoaded('actions')
            ? $order->actions->sortBy('id')->values()
            : $order->actions()->with('actor:id,name')->orderBy('id')->get();

        return $actions
            ->map(fn (TravelOrderAction $action): array => [
                'id' => $action->id,
                'event' => $action->event->value,
                'event_label' => $action->event->label(),
                'step_label' => $action->step_label,
                'step_index' => $action->step_index,
                'revision_id' => $action->revision_id,
                'actor' => $action->actor?->name,
                'remarks' => $action->is_internal && ! $includeInternal
                    ? null
                    : $action->remarks,
                'created_at' => $action->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * The return-and-resubmission rounds, oldest first.
     *
     * @return array<int, array{
     *     revision_number: int,
     *     returned_by: string|null,
     *     returned_at: string|null,
     *     return_remarks: string|null,
     *     returned_from_step: string|null,
     *     revised_by: string|null,
     *     revision_remarks: string|null,
     *     change_type: string,
     *     changed_fields: array<int, string>|null,
     *     previous_data: array<string, mixed>|null,
     *     new_data: array<string, mixed>|null,
     *     resubmitted_at: string|null
     * }>
     */
    public function revisions(TravelOrder $order): array
    {
        $order->loadMissing(['revisions.returnedBy:id,name', 'revisions.revisedBy:id,name']);

        return $order->revisions
            ->map(fn (TravelOrderRevision $revision): array => [
                'revision_number' => (int) $revision->revision_number,
                'returned_by' => $revision->returnedBy?->name,
                'returned_at' => $revision->returned_at?->toIso8601String(),
                'return_remarks' => $revision->return_remarks,
                'returned_from_step' => $order->returned_from_step,
                'revised_by' => $revision->revisedBy?->name,
                'revision_remarks' => $revision->revision_remarks,
                'change_type' => $revision->change_type->value,
                'changed_fields' => $revision->changed_fields,
                'previous_data' => $revision->previous_data,
                'new_data' => $revision->new_data,
                'resubmitted_at' => $revision->resubmitted_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{index: int, step: WorkflowStep|null}>
     */
    private function stepsFor(TravelOrder $order): array
    {
        $ids = $order->routed_steps;

        if (is_array($ids) && $ids !== []) {
            $found = WorkflowStep::query()
                ->with(['stepType', 'office'])
                ->whereIn('id', array_map(intval(...), $ids))
                ->get()
                ->keyBy('id');

            $entries = [];

            foreach (array_values($ids) as $index => $id) {
                $step = $found->get((int) $id);
                $entries[] = [
                    'index' => $index,
                    'step' => $step instanceof WorkflowStep ? $step : null,
                ];
            }

            return $entries;
        }

        if ($order->office === null) {
            return [];
        }

        return $this->routes
            ->stepsForOffice($order->office)
            ->values()
            ->map(fn (WorkflowStep $step, int $index): array => [
                'index' => $index,
                'step' => $step,
            ])
            ->all();
    }

    private function statusFor(
        TravelOrder $order,
        int $index,
        ?int $currentIndex,
        ?int $returnedIndex,
        bool $skipped,
    ): string {
        if ($skipped && $order->status !== TravelOrderStatus::Submitted) {
            return 'skipped';
        }

        return match ($order->status) {
            TravelOrderStatus::Draft => 'planned',
            TravelOrderStatus::Returned => $returnedIndex === null
                ? 'upcoming'
                : match (true) {
                    $index < $returnedIndex => 'cleared',
                    $index === $returnedIndex => 'returned',
                    default => 'upcoming',
                },
            TravelOrderStatus::Completed, TravelOrderStatus::Approved => 'cleared',
            TravelOrderStatus::Cancelled, TravelOrderStatus::Rejected => 'cancelled',
            default => match (true) {
                $currentIndex === null => 'upcoming',
                $index < $currentIndex => $skipped ? 'skipped' : 'cleared',
                $index === $currentIndex => 'current',
                default => 'upcoming',
            },
        };
    }

    /**
     * The people holding a step right now.
     *
     * Only a step the order is actually waiting at has holders. Closed copies
     * at earlier steps are history and belong to the audit trail.
     *
     * @return array<int, array{id: int, name: string, status: string}>
     */
    private function holdersFor(TravelOrder $order, int $index, string $status): array
    {
        if ($status !== 'current') {
            return [];
        }

        return $order->deliveries
            ->where('step_index', $index)
            ->filter(fn (TravelOrderDelivery $delivery): bool => $delivery->status->isOpen())
            ->map(fn (TravelOrderDelivery $delivery): array => [
                'id' => $delivery->user->id,
                'name' => $delivery->user->name,
                'status' => $delivery->status->value,
            ])
            ->values()
            ->all();
    }

    private function returnedIndex(TravelOrder $order): ?int
    {
        if ($order->returned_from_step_index !== null) {
            return (int) $order->returned_from_step_index;
        }

        $action = $order->actions
            ->where('event', WorkflowEvent::Returned)
            ->sortByDesc('id')
            ->first();

        return $action?->step_index;
    }
}
