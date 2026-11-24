<?php

namespace App\Console\Commands;

use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\TravelOrderDelivery;
use Illuminate\Console\Command;

/**
 * @phpstan-type Finding array{
 *     severity: 'error'|'warning',
 *     code: string,
 *     travel_order_id: int|null,
 *     delivery_id: int|null,
 *     message: string
 * }
 */
class CheckTravelOrderIntegrity extends Command
{
    protected $signature = 'travel-orders:check-integrity
        {--json : Emit the findings as JSON}
        {--fail-on-warning : Return a failure status when warnings are present}';

    protected $description = 'Check travel-order route, delivery, and holder integrity without changing data.';

    public function handle(): int
    {
        $findings = $this->collectFindings();
        $errors = $this->countSeverity($findings, 'error');
        $warnings = $this->countSeverity($findings, 'warning');

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => $errors === 0,
                'errors' => $errors,
                'warnings' => $warnings,
                'findings' => $findings,
            ], JSON_THROW_ON_ERROR));
        } else {
            $rows = array_map(static fn (array $finding): array => [
                $finding['severity'],
                $finding['code'],
                $finding['travel_order_id'] ?? '—',
                $finding['delivery_id'] ?? '—',
                $finding['message'],
            ], $findings);

            $this->table(['Severity', 'Code', 'Order', 'Delivery', 'Finding'], $rows);
            $this->line(sprintf(
                'Travel-order integrity: %d error(s), %d warning(s).',
                $errors,
                $warnings,
            ));
        }

        if ($errors > 0 || ($this->option('fail-on-warning') && $warnings > 0)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, Finding>
     */
    private function collectFindings(): array
    {
        /** @var array<int, Finding> $findings */
        $findings = [];

        TravelOrder::query()
            ->with('deliveries.user')
            ->chunkById(500, function ($orders) use (&$findings): void {
                foreach ($orders as $order) {
                    foreach ($this->checkOrder($order) as $finding) {
                        $findings[] = $finding;
                    }
                }
            });

        foreach ($this->orphanDeliveries() as $finding) {
            $findings[] = $finding;
        }

        return $findings;
    }

    /**
     * @return array<int, Finding>
     */
    private function checkOrder(TravelOrder $order): array
    {
        /** @var array<int, Finding> $findings */
        $findings = [];
        $route = is_array($order->routed_steps) ? array_values($order->routed_steps) : [];
        $index = $order->current_step_index;
        $hasValidIndex = is_int($index) && $index >= 0 && ($route === [] || $index < count($route));

        if ($order->status === TravelOrderStatus::Submitted) {
            if ($order->workflow_id === null) {
                $this->add($findings, 'error', 'submitted_without_workflow', $order->id, null, 'Submitted order has no frozen workflow.');
            }

            if ($route === []) {
                $this->add($findings, 'error', 'submitted_without_route', $order->id, null, 'Submitted order has no frozen route.');
            }

            if ($order->submitted_at === null) {
                $this->add($findings, 'error', 'submitted_without_timestamp', $order->id, null, 'Submitted order has no submission timestamp.');
            }

            $reviewFields = $order->routed_step_review_fields;
            if (is_array($reviewFields) && count($reviewFields) !== count($route)) {
                $this->add($findings, 'error', 'frozen_review_map_mismatch', $order->id, null, 'Frozen review-field map does not match the frozen route length.');
            }

            if (! $hasValidIndex) {
                $this->add($findings, 'error', 'invalid_current_step', $order->id, null, 'Submitted order has no current step inside its frozen route.');
            } else {
                /** @var array<int, bool> $holders */
                $holders = [];
                foreach ($order->deliveries as $delivery) {
                    if ($delivery->status->isOpen() && $delivery->step_index === $index) {
                        $holders[$delivery->user_id] = true;
                    }
                }

                if ($holders === []) {
                    $this->add($findings, 'error', 'no_open_delivery', $order->id, null, 'Submitted order has no open delivery at its current step.');
                }

                if ($order->recipient_user_id === null) {
                    $this->add($findings, 'error', 'missing_recipient', $order->id, null, 'Submitted order has no recipient pointer.');
                } elseif (! isset($holders[$order->recipient_user_id])) {
                    $this->add($findings, 'error', 'recipient_not_holder', $order->id, null, 'Recipient pointer does not match an open holder at the current step.');
                }
            }
        } else {
            foreach ($order->deliveries as $delivery) {
                if ($delivery->status->isOpen()) {
                    $this->add($findings, 'error', 'stale_open_delivery', $order->id, $delivery->id, 'Open delivery found on an order that is not submitted.');
                }
            }

            if ($order->current_step_index !== null || $order->recipient_user_id !== null) {
                $this->add($findings, 'warning', 'stale_route_pointer', $order->id, null, 'Closed order retains a current-step or recipient pointer.');
            }
        }

        foreach ($order->deliveries as $delivery) {
            if ($route !== [] && ($delivery->step_index < 0 || $delivery->step_index >= count($route))) {
                $this->add($findings, 'error', 'delivery_step_out_of_route', $order->id, $delivery->id, 'Delivery step is outside the frozen route bounds.');
            }

            if ($delivery->user === null) {
                $this->add($findings, 'error', 'missing_delivery_user', $order->id, $delivery->id, 'Delivery points to a user that no longer exists.');
            }

            if ($delivery->status === TravelOrderDeliveryStatus::Acted && $delivery->acted_at === null) {
                $this->add($findings, 'error', 'acted_without_timestamp', $order->id, $delivery->id, 'Acted delivery has no acted-at timestamp.');
            }

            if ($delivery->status === TravelOrderDeliveryStatus::Received && $delivery->received_at === null) {
                $this->add($findings, 'warning', 'received_without_timestamp', $order->id, $delivery->id, 'Received delivery has no received-at timestamp.');
            }

            if (
                $order->status === TravelOrderStatus::Submitted
                && $delivery->status->isOpen()
                && $hasValidIndex
                && $delivery->step_index !== $index
            ) {
                $this->add($findings, 'warning', 'open_delivery_at_other_step', $order->id, $delivery->id, 'Open delivery remains at a step other than the current one.');
            }
        }

        return $findings;
    }

    /**
     * @return array<int, Finding>
     */
    private function orphanDeliveries(): array
    {
        /** @var array<int, Finding> $findings */
        $findings = [];

        $orphans = TravelOrderDelivery::query()
            ->whereNotExists(TravelOrder::query()
                ->select('id')
                ->whereColumn('travel_orders.id', 'travel_order_deliveries.travel_order_id'))
            ->get(['id', 'travel_order_id']);

        foreach ($orphans as $delivery) {
            $this->add(
                $findings,
                'error',
                'orphan_delivery',
                null,
                $delivery->id,
                'Delivery points to a travel order that no longer exists.',
            );
        }

        return $findings;
    }

    /**
     * @param  array<int, Finding>  $findings
     *
     * @param-out  array<int, Finding>  $findings
     *
     * @param  'error'|'warning'  $severity
     */
    private function add(array &$findings, string $severity, string $code, ?int $orderId, ?int $deliveryId, string $message): void
    {
        $findings[] = [
            'severity' => $severity,
            'code' => $code,
            'travel_order_id' => $orderId,
            'delivery_id' => $deliveryId,
            'message' => $message,
        ];
    }

    /**
     * @param  array<int, Finding>  $findings
     */
    private function countSeverity(array $findings, string $severity): int
    {
        return count(array_filter($findings, static fn (array $finding): bool => $finding['severity'] === $severity));
    }
}
