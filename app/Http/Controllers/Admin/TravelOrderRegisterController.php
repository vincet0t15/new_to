<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use App\Services\TravelOrderApprovalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TravelOrderRegisterController extends Controller
{
    public function __construct(
        private readonly TravelOrderApprovalService $approvals,
    ) {
        //
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $orders = TravelOrder::query()
            ->with(['office:id,office_name', 'employee:id,first_name,last_name', 'creator:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('purpose', 'like', '%'.$search.'%')
                        ->orWhere('destination', 'like', '%'.$search.'%')
                        ->orWhere('to_number', 'like', '%'.$search.'%')
                        ->orWhere('control_no', 'like', '%'.$search.'%')
                        ->orWhere('id', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function (TravelOrder $order): TravelOrder {
                if ($order->status === TravelOrderStatus::Submitted) {
                    $order->setAttribute('reassign_candidates', $this->approvals
                        ->reassignCandidates($order)
                        ->map(fn ($user) => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                        ])
                        ->values()
                        ->all());
                } else {
                    $order->setAttribute('reassign_candidates', []);
                }

                return $order;
            });

        return Inertia::render('admin/travel-orders/index', [
            'travelOrders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => array_map(
                fn (TravelOrderStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                TravelOrderStatus::selectable(),
            ),
        ]);
    }
}
