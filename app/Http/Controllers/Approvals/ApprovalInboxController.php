<?php

namespace App\Http\Controllers\Approvals;

use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use App\Models\TravelOrderAction;
use App\Models\TravelOrderDelivery;
use App\Models\User;
use App\Repositories\Contracts\TravelOrderRepository;
use App\Services\TravelOrderApprovalService;
use App\Services\TravelOrderRoutePresenter;
use Illuminate\Http\Request;
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

        $deliveries = $this->travelOrders
            ->paginateInbox($user)
            ->through(function (TravelOrder $order) use ($prefix): array {
                $step = $this->approvals->currentStep($order);
                $delivery = $order->deliveries->first();

                return [
                    'id' => $order->id,
                    'control_no' => $order->control_no,
                    'to_number' => $order->to_number,
                    'purpose' => $order->purpose,
                    'destination' => $order->destination,
                    'date_from' => $order->date_from?->toDateString(),
                    'date_to' => $order->date_to?->toDateString(),
                    'submitted_at' => $order->submitted_at?->toIso8601String(),
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
            'portal' => $prefix,
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

        $history = $this->travelOrders
            ->paginateHistory($user)
            ->through(function (TravelOrder $order) use ($prefix): array {
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
                    $stepLabel = $this->approvals->currentStep($order)?->displayLabel();
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
            'employee',
            'creator',
            'returnedBy',
            'workflow',
            'deliveries.user',
            'actions.actor',
            'travelClassification',
            'fundSource',
            'origin',
            'destinationLocation',
            'officialStation',
            'expenseTypes',
            'transportationModes',
        ]);

        $prefix = $this->portalPrefix($request);
        $step = $this->approvals->currentStep($travelOrder);
        $delivery = $travelOrder->activeDeliveryFor($user);
        $isHolder = $delivery !== null;
        $isSubmitted = $travelOrder->status === TravelOrderStatus::Submitted;
        $candidates = $this->approvals->forwardCandidates($travelOrder, $user);

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
            'can' => [
                'receive' => $isSubmitted && $isHolder && $delivery->status === TravelOrderDeliveryStatus::Pending,
                'approve' => $isSubmitted && $isHolder && $step?->stepType->is_signatory === true,
                'acknowledge' => $isSubmitted && $isHolder && $step?->stepType->is_signatory === false,
                'return' => $isSubmitted && $isHolder,
                'forward' => $isSubmitted && $isHolder && $candidates->isNotEmpty(),
            ],
            'urls' => [
                'inbox' => route("{$prefix}.inbox"),
                'receive' => route("{$prefix}.travel-orders.receive", $travelOrder),
                'approve' => route("{$prefix}.travel-orders.approve", $travelOrder),
                'acknowledge' => route("{$prefix}.travel-orders.acknowledge", $travelOrder),
                'return' => route("{$prefix}.travel-orders.return", $travelOrder),
                'forward' => route("{$prefix}.travel-orders.forward", $travelOrder),
            ],
        ]);
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
