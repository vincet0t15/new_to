<?php

namespace App\Http\Controllers\Approvals;

use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\FundSource;
use App\Models\TravelOrder;
use App\Models\TravelOrderAction;
use App\Models\TravelOrderDelivery;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Repositories\Contracts\TravelOrderRepository;
use App\Services\ItinerarySignatoryResolver;
use App\Services\SignatoryResolver;
use App\Services\TravelOrderApprovalService;
use App\Services\TravelOrderRoutePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The shared approver inbox.
 *
 * The same controller serves /department-head and /approvals. A portal is a
 * workspace, not a second permission system: both routes use the same
 * deliveries, and who may act is decided by the designation on the current
 * step, not by the prefix the person happened to enter through.
 */
class ApprovalInboxController extends Controller
{
    public function __construct(
        private readonly TravelOrderRepository $travelOrders,
        private readonly TravelOrderApprovalService $approvals,
        private readonly TravelOrderRoutePresenter $presenter,
        private readonly ItinerarySignatoryResolver $itinerarySignatories,
    ) {
        //
    }

    /**
     * Travel orders currently waiting on this account.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $prefix = $this->portalPrefix($request);
        $inbox = $this->travelOrders->paginateInbox($user);
        $currentSteps = $this->currentStepsFor($inbox->getCollection());

        $deliveries = $inbox->through(function (TravelOrder $order) use ($prefix, $currentSteps): array {
            $step = $this->currentStepFromMap($order, $currentSteps);
            $delivery = $order->deliveries->first();
            $waitingSince = $delivery instanceof TravelOrderDelivery
                ? ($delivery->created_at ?? $order->submitted_at)
                : $order->submitted_at;

            return [
                'id' => $order->id,
                'control_no' => $order->control_no,
                'to_number' => $order->to_number,
                'purpose' => $order->purpose,
                'destination' => $order->destination,
                'date_from' => $order->date_from?->toDateString(),
                'date_to' => $order->date_to?->toDateString(),
                'submitted_at' => $order->submitted_at?->toIso8601String(),
                'waiting_days' => $waitingSince === null
                    ? null
                    : (int) $waitingSince->diffInDays(now()),
                'employee' => $order->employee?->full_name,
                'office' => $order->office?->office_name,
                'step_label' => $step?->displayLabel(),
                'is_signatory' => $step?->stepType->is_signatory ?? true,
                'delivery_status' => $delivery instanceof TravelOrderDelivery
                    ? $delivery->status->value
                    : TravelOrderDeliveryStatus::Pending->value,
                'url' => "/{$prefix}/travel-orders/{$order->id}",
            ];
        });

        return Inertia::render('approvals/inbox', [
            'deliveries' => $deliveries,
            'stats' => $this->travelOrders->processingCounts($user),
            'portal' => $prefix,
            'stalledAfterDays' => max(
                1,
                (int) config('travel-orders.approval.stalled_after_days', 3),
            ),
        ]);
    }

    /**
     * Every order that was ever delivered to this account, including the ones
     * already acted on. This is the counterpart to the inbox: the inbox is the
     * work queue, while the history is the permanent record.
     */
    public function history(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $prefix = $this->portalPrefix($request);
        $historyPage = $this->travelOrders->paginateHistory($user);
        $currentSteps = $this->currentStepsFor($historyPage->getCollection());

        $history = $historyPage->through(function (TravelOrder $order) use ($prefix, $currentSteps): array {
            $delivery = $order->deliveries->first();
            $action = $delivery === null
                ? $order->actions->first()
                : $order->actions->firstWhere('step_index', $delivery->step_index);
            $deliveryStatus = $delivery?->status;

            $stepLabel = $deliveryStatus === TravelOrderDeliveryStatus::Acted
                ? $action?->step_label
                : null;

            if (
                $stepLabel === null
                && $delivery !== null
                && $order->status === TravelOrderStatus::Submitted
                && $order->current_step_index === $delivery->step_index
            ) {
                $stepLabel = $this->currentStepFromMap($order, $currentSteps)?->displayLabel();
            }

            $actionAt = match ($deliveryStatus) {
                TravelOrderDeliveryStatus::Acted => $action instanceof TravelOrderAction
                    ? ($action->created_at ?? $delivery->acted_at)
                    : $delivery->acted_at,
                TravelOrderDeliveryStatus::Received => $delivery->received_at,
                TravelOrderDeliveryStatus::Pending => $delivery->created_at,
                TravelOrderDeliveryStatus::Closed => $delivery->updated_at,
                null => null,
            };

            return [
                'id' => $order->id,
                'control_no' => $order->control_no,
                'to_number' => $order->to_number,
                'purpose' => $order->purpose,
                'destination' => $order->destination,
                'date_from' => $order->date_from?->toDateString(),
                'date_to' => $order->date_to?->toDateString(),
                'employee' => $order->employee?->full_name,
                'office' => $order->office?->office_name,
                'step_label' => $stepLabel ?? ($delivery === null
                    ? null
                    : 'Step '.($delivery->step_index + 1)),
                'your_action' => $this->actionLabel($deliveryStatus, $action?->event->label()),
                'your_action_event' => $deliveryStatus === TravelOrderDeliveryStatus::Acted
                    ? $action?->event->value
                    : $deliveryStatus?->value,
                'action_at' => $actionAt?->toIso8601String(),
                'delivery_status' => $deliveryStatus instanceof TravelOrderDeliveryStatus
                    ? $deliveryStatus->value
                    : TravelOrderDeliveryStatus::Closed->value,
                'status' => $order->status->value,
                'url' => "/{$prefix}/travel-orders/{$order->id}",
            ];
        });

        return Inertia::render('approvals/history', [
            'historyItems' => $history,
            'portal' => $prefix,
        ]);
    }

    /**
     * How the account's latest copy reads on the history screen.
     */
    private function actionLabel(
        ?TravelOrderDeliveryStatus $status,
        ?string $eventLabel,
    ): string {
        return match ($status) {
            TravelOrderDeliveryStatus::Pending => __('Waiting'),
            TravelOrderDeliveryStatus::Received => __('Received'),
            TravelOrderDeliveryStatus::Acted => $eventLabel ?? __('Acted'),
            TravelOrderDeliveryStatus::Closed, null => __('Closed'),
        };
    }

    /**
     * Show one order with its route, audit trail, and available actions.
     */
    public function show(Request $request, TravelOrder $travelOrder): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        abort_unless(
            $travelOrder->isOwnedBy($user)
                || $travelOrder->deliveries()->where('user_id', $user->id)->exists(),
            403,
        );

        $travelOrder->load([
            'office',
            'employee.position',
            'employee.office',
            'creator',
            'returnedBy',
            'workflow',
            'deliveries.user',
            'actions.actor',
            'revisions.returnedBy',
            'revisions.revisedBy',
            'travelClassification',
            'fundSource',
            'origin',
            'destinationLocation',
            'officialStation',
            'expenseTypes',
            'transportationModes',
            'itineraries.fromLocation',
            'itineraries.toLocation',
            'itineraries.transportationMode',
            'tripTickets.driver',
            'tripTickets.creator',
            'tripTickets.fuelTypeRecord',
        ]);

        $prefix = $this->portalPrefix($request);
        $step = $this->approvals->currentStep($travelOrder);
        $delivery = $travelOrder->activeDeliveryFor($user);
        $isHolder = $delivery !== null;
        $isSubmitted = $travelOrder->status === TravelOrderStatus::Submitted;
        $candidates = $this->approvals->forwardCandidates($travelOrder, $user);
        $stepName = $step?->stepType->name;
        $isBudgetStep = in_array($stepName, ['Budget Review', 'Budget'], true);
        $isHrmoStep = in_array($stepName, ['HRMO Review', 'HRMO'], true);

        return Inertia::render('approvals/show', [
            'travelOrder' => $travelOrder,
            'route' => $this->presenter->route($travelOrder),
            'timeline' => $this->presenter->timeline($travelOrder),
            'revisions' => $this->presenter->revisions($travelOrder),
            'currentStep' => $step === null ? null : [
                'label' => $step->displayLabel(),
                'office' => $step->office->office_name
                    ?? $travelOrder->office?->office_name,
                'is_signatory' => $step->stepType->is_signatory,
            ],
            'myDeliveryStatus' => $delivery?->status->value,
            'forwardCandidates' => $candidates
                ->map(fn (User $candidate): array => [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                ])
                ->values()
                ->all(),
            'fundSources' => FundSource::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (FundSource $fundSource): array => [
                    'id' => $fundSource->id,
                    'name' => $fundSource->name,
                ])
                ->values()
                ->all(),
            'can' => [
                'receive' => $isSubmitted && $isHolder && $delivery->status === TravelOrderDeliveryStatus::Pending,
                'approve' => $isSubmitted && $isHolder && $step?->stepType->is_signatory === true,
                'acknowledge' => $isSubmitted && $isHolder && $step?->stepType->is_signatory === false,
                'return' => $isSubmitted && $isHolder,
                'forward' => $isSubmitted && $isHolder && $candidates->isNotEmpty(),
                'earmark' => $isSubmitted && $isHolder && $isBudgetStep && $user->can('travel-orders.earmark'),
                'assignFundSource' => $isSubmitted && $isHolder && $isBudgetStep && $user->can('travel-orders.assign-fund-source'),
                'record' => $isSubmitted && $isHolder && $isHrmoStep && $user->can('travel-orders.record'),
            ],
            'urls' => [
                'inbox' => route("{$prefix}.inbox"),
                'receive' => route("{$prefix}.travel-orders.receive", $travelOrder),
                'approve' => route("{$prefix}.travel-orders.approve", $travelOrder),
                'acknowledge' => route("{$prefix}.travel-orders.acknowledge", $travelOrder),
                'return' => route("{$prefix}.travel-orders.return", $travelOrder),
                'forward' => route("{$prefix}.travel-orders.forward", $travelOrder),
                'earmark' => route("{$prefix}.travel-orders.earmark", $travelOrder),
                'assignFundSource' => route("{$prefix}.travel-orders.assign-fund-source", $travelOrder),
                'record' => route("{$prefix}.travel-orders.record", $travelOrder),
            ],
            'letterhead' => config('travel-orders.letterhead'),
            'signatories' => $travelOrder->signatories
                ?? app(SignatoryResolver::class)->for($travelOrder),
            'itinerarySignatories' => $this->itinerarySignatories->for($travelOrder),
            'tripTickets' => $travelOrder->tripTickets->all(),
        ]);
    }

    /**
     * Preload current workflow steps for a page of orders.
     *
     * @param  Collection<int, TravelOrder>  $orders
     * @return array<int, WorkflowStep>
     */
    private function currentStepsFor(Collection $orders): array
    {
        $ids = $orders
            ->map(function (TravelOrder $order): ?int {
                $index = $order->current_step_index;

                if ($order->status !== TravelOrderStatus::Submitted
                    || $index === null
                    || ! is_array($order->routed_steps)
                    || ! isset($order->routed_steps[$index])) {
                    return null;
                }

                return (int) $order->routed_steps[$index];
            })
            ->filter(fn (?int $id): bool => $id !== null)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return WorkflowStep::query()
            ->with(['stepType', 'office'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * @param  array<int, WorkflowStep>  $currentSteps
     */
    private function currentStepFromMap(TravelOrder $order, array $currentSteps): ?WorkflowStep
    {
        $index = $order->current_step_index;
        $ids = $order->routed_steps;

        if ($index === null || ! is_array($ids) || ! isset($ids[$index])) {
            return null;
        }

        $step = $currentSteps[(int) $ids[$index]] ?? null;

        return $step instanceof WorkflowStep ? $step : null;
    }

    /**
     * The route prefix the current screen belongs to.
     */
    private function portalPrefix(Request $request): string
    {
        return $request->routeIs('department-head.*')
            ? 'department-head'
            : 'approvals';
    }
}
