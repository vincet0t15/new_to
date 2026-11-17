<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TravelOrderRegisterController extends Controller
{
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
                        ->orWhere('control_no', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/travel-orders/index', [
            'travelOrders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }
}
