<?php

namespace App\Http\Controllers\Portals;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\TravelOrderRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The two portal landings that show real work data.
 *
 * The approvals portal has no dashboard of its own: its inbox is the landing
 * page, so its counts travel with the inbox response instead.
 */
final class PortalDashboardController extends Controller
{
    public function __construct(private readonly TravelOrderRepository $orders)
    {
        //
    }

    /**
     * The personal workspace: only orders this account filed.
     */
    public function employee(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $recentOrders = $this->orders->paginateForCreator($user, null, 5)
            ->through(fn ($order) => [
                'id' => $order->id,
                'control_no' => $order->control_no,
                'to_number' => $order->to_number,
                'purpose' => $order->purpose,
                'destination' => $order->destination,
                'date_from' => $order->date_from?->toDateString(),
                'date_to' => $order->date_to?->toDateString(),
                'status' => $order->status->value,
                'updated_at' => $order->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('portals/employee/dashboard', [
            'stats' => $this->orders->countStatusesForCreator($user),
            'recentOrders' => $recentOrders->items(),
        ]);
    }

    /**
     * The department head processing workspace.
     */
    public function departmentHead(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $pendingItems = $this->orders->paginateInbox($user, 5)
            ->through(function ($order): array {
                $delivery = $order->deliveries->first();

                return [
                    'id' => $order->id,
                    'control_no' => $order->control_no,
                    'to_number' => $order->to_number,
                    'purpose' => $order->purpose,
                    'destination' => $order->destination,
                    'employee' => $order->employee?->full_name,
                    'office' => $order->office?->office_name,
                    'submitted_at' => $order->submitted_at?->toIso8601String(),
                    'delivery_status' => $delivery?->status->value,
                ];
            });

        return Inertia::render('portals/department-head/dashboard', [
            'stats' => $this->orders->processingCounts($user),
            'pendingItems' => $pendingItems->items(),
        ]);
    }
}
