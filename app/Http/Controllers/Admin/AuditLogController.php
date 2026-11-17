<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelOrderAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $event = $request->string('event')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $actions = TravelOrderAction::query()
            ->with(['travelOrder:id,to_number,control_no', 'actor:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('event', 'like', '%'.$search.'%')
                        ->orWhere('remarks', 'like', '%'.$search.'%');
                });
            })
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/audit/index', [
            'actions' => $actions,
            'filters' => [
                'search' => $search,
                'event' => $event,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'events' => TravelOrderAction::query()
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
        ]);
    }
}
