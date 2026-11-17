<?php

namespace App\Services;

use App\Enums\TravelOrderChangeType;
use App\Models\TravelOrder;
use App\Models\WorkflowStep;

/**
 * Compares a returned travel order with the content that was sent back.
 *
 * Only the fields an approver can care about are tracked. The snapshot is
 * taken when the order is returned, before the creator edits it, because by
 * resubmission time the old values are otherwise gone.
 */
final class TravelOrderChangeDetector
{
    /**
     * The content of the order at this moment, normalised for comparison.
     *
     * @return array<string, mixed>
     */
    public function snapshot(TravelOrder $order): array
    {
        $order->loadMissing(['expenseTypes', 'transportationModes']);

        $snapshot = [];

        foreach ($this->trackedFields() as $field) {
            if ($field === 'expense_type_ids') {
                $snapshot[$field] = $order->expenseTypes
                    ->pluck('id')
                    ->map(fn (int|string $id): int => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                continue;
            }

            if ($field === 'transportation_mode_ids') {
                $snapshot[$field] = $order->transportationModes
                    ->pluck('id')
                    ->map(fn (int|string $id): int => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                continue;
            }

            $value = $order->getAttribute($field);

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            if (str_ends_with($field, '_id') && $value !== null) {
                $value = (int) $value;
            }

            $snapshot[$field] = $value;
        }

        return $snapshot;
    }

    /**
     * The fields whose value differs from the snapshot, with their new values.
     *
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    public function changedFields(array $previous, TravelOrder $order): array
    {
        $current = $this->snapshot($order);
        $changed = [];

        foreach ($current as $field => $value) {
            if (! array_key_exists($field, $previous)) {
                continue;
            }

            if ($previous[$field] !== $value) {
                $changed[$field] = $value;
            }
        }

        return $changed;
    }

    /**
     * A change is material when it touches a field an approver judged.
     *
     * @param  array<string, mixed>  $changed
     */
    public function changeType(array $changed): TravelOrderChangeType
    {
        $material = array_intersect(array_keys($changed), $this->materialFields());

        return $material === []
            ? TravelOrderChangeType::Minor
            : TravelOrderChangeType::Material;
    }

    /**
     * The index the resubmission should deliver to.
     *
     * The returning step is always the baseline. The configured policy can
     * move a material change earlier, never later, because the office that
     * returned the order still has to see the correction.
     *
     * @param  array<int, string>  $changedFields
     */
    public function resumeIndex(
        TravelOrder $order,
        TravelOrderChangeType $changeType,
        array $changedFields,
    ): int {
        $base = $order->returned_from_step_index !== null
            ? (int) $order->returned_from_step_index
            : 0;

        if ($changeType !== TravelOrderChangeType::Material) {
            return $base;
        }

        $policy = config('travel-orders.resubmission.policy', 'resume');

        return match ($policy) {
            'restart' => 0,
            'restart_from_first_affected' => min(
                $base,
                $this->firstAffectedIndex($order, $changedFields) ?? 0,
            ),
            default => $base,
        };
    }

    /**
     * The earliest step in the frozen route that a changed field affects.
     *
     * A field with no mapping is treated as affecting the whole chain, so the
     * safe answer is the first step. The mapping lives in configuration
     * because it depends on how an office writes its chain.
     *
     * @param  array<int, string>  $changedFields
     */
    public function firstAffectedIndex(TravelOrder $order, array $changedFields): ?int
    {
        /** @var array<string, array<int, string>> $map */
        $map = config('travel-orders.resubmission.affected_step_types', []);

        $stepTypeSlugs = [];

        foreach ($changedFields as $field) {
            if (! isset($map[$field]) || $map[$field] === []) {
                return 0;
            }

            foreach ($map[$field] as $slug) {
                $stepTypeSlugs[$slug] = true;
            }
        }

        if ($stepTypeSlugs === []) {
            return null;
        }

        $ids = $order->routed_steps;

        if (! is_array($ids)) {
            return null;
        }

        foreach (array_values($ids) as $index => $id) {
            $step = WorkflowStep::query()
                ->with('stepType')
                ->find((int) $id);

            if ($step !== null && isset($stepTypeSlugs[$step->stepType->slug])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function materialFields(): array
    {
        /** @var array<int, string> $fields */
        $fields = config('travel-orders.resubmission.material_fields', []);

        return $fields;
    }

    /**
     * @return array<int, string>
     */
    private function trackedFields(): array
    {
        /** @var array<int, string> $fields */
        $fields = config('travel-orders.resubmission.tracked_fields', []);

        return $fields;
    }
}
