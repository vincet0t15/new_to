<?php

namespace App\Services;

use App\Enums\WorkflowEvent;
use App\Models\TravelOrder;
use App\Models\TravelOrderAction;
use App\Models\User;
use App\Models\WorkflowStep;

/**
 * Writes the immutable audit trail for a travel order.
 *
 * Every state change in the routing engine goes through this class so the
 * order of events, the step label snapshot, and the acting account are always
 * recorded the same way. The row is append-only: nothing here updates or
 * deletes an earlier entry.
 */
final class TravelOrderAudit
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function record(
        TravelOrder $order,
        WorkflowEvent $event,
        ?User $actor = null,
        ?WorkflowStep $step = null,
        ?int $stepIndex = null,
        ?string $remarks = null,
        ?array $meta = null,
        ?int $revisionId = null,
    ): TravelOrderAction {
        return $order->actions()->create([
            'step_index' => $stepIndex,
            'step_label' => $step?->displayLabel(),
            'event' => $event,
            'revision_id' => $revisionId,
            'actor_user_id' => $actor?->id,
            'remarks' => $remarks,
            'meta' => $meta,
        ]);
    }
}
