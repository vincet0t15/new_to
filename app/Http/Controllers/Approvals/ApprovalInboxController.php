<?php

namespace App\Http\Controllers\Approvals;

use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
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
