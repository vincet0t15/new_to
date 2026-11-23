<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\FuelAllocation;
use App\Models\TravelOrder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $lowLiters = (float) config('travel-orders.fuel.low_balance_liters', 20);
        $lowAmount = (float) config('travel-orders.fuel.low_balance_amount', 1000);

        $allocated = FuelAllocation::query()
            ->join('offices', 'offices.id', '=', 'fuel_allocations.office_id')
            ->leftJoin('fuel_types', 'fuel_types.id', '=', 'fuel_allocations.fuel_type_id')
            ->where('fuel_allocations.status', FuelAllocation::STATUS_ACTIVE)
            ->selectRaw('offices.id as office_id, offices.office_name, fuel_allocations.fuel_type_id, COALESCE(NULLIF(fuel_types.name, \'\'), NULLIF(fuel_allocations.fuel_type, \'\'), \'Unspecified\') as fuel_type')
            ->selectRaw('COALESCE(SUM(fuel_allocations.allocated_liters),0) as allocated_liters')
            ->selectRaw('COALESCE(SUM(fuel_allocations.allocated_amount),0) as allocated_amount')
            ->groupBy('offices.id', 'offices.office_name', 'fuel_allocations.fuel_type_id', 'fuel_type')
            ->get();

        $consumed = DB::table('trip_tickets')
            ->join('travel_orders', 'travel_orders.id', '=', 'trip_tickets.travel_order_id')
            ->leftJoin('fuel_types', 'fuel_types.id', '=', 'trip_tickets.fuel_type_id')
            ->where('trip_tickets.status', 'completed')
            ->selectRaw('COALESCE(trip_tickets.fuel_source_office_id, travel_orders.office_id) as office_id, trip_tickets.fuel_type_id, COALESCE(NULLIF(fuel_types.name, \'\'), NULLIF(trip_tickets.fuel_type, \'\'), \'Unspecified\') as fuel_type')
            ->selectRaw('COALESCE(SUM(COALESCE(trip_tickets.fuel_consumed,0)),0) as actual_liters')
            ->selectRaw('COALESCE(SUM(COALESCE(trip_tickets.fuel_cost,0)),0) as fuel_cost')
            ->groupByRaw('COALESCE(trip_tickets.fuel_source_office_id, travel_orders.office_id), trip_tickets.fuel_type_id, fuel_type')
            ->get()
            ->keyBy(fn ($r) => $r->office_id.'|'.($r->fuel_type_id ?? 'null').'|'.$r->fuel_type);

        $fuelBalances = $allocated->map(function ($row) use ($consumed) {
            $key = $row->office_id.'|'.($row->fuel_type_id ?? 'null').'|'.$row->fuel_type;
            $c = $consumed->get($key);
            $remainingLiters = (float) $row->allocated_liters - (float) ($c?->actual_liters ?? 0);
            $remainingAmount = (float) $row->allocated_amount - (float) ($c?->fuel_cost ?? 0);

            return [
                'office_id' => (int) $row->office_id,
                'office_name' => $row->office_name,
                'fuel_type' => $row->fuel_type,
                'allocated_liters' => (float) $row->allocated_liters,
                'allocated_amount' => (float) $row->allocated_amount,
                'actual_liters' => (float) ($c?->actual_liters ?? 0),
                'fuel_cost' => (float) ($c?->fuel_cost ?? 0),
                'remaining_liters' => $remainingLiters,
                'remaining_amount' => $remainingAmount,
            ];
        })->values();

        $alerts = $fuelBalances
            ->filter(fn (array $row): bool => $row['remaining_liters'] < 0 || $row['remaining_amount'] < 0 || $row['remaining_liters'] <= $lowLiters || $row['remaining_amount'] <= $lowAmount)
            ->sortBy([['remaining_liters', 'asc'], ['remaining_amount', 'asc']])
            ->take(8)
            ->values();

        $toStats = TravelOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('portals/admin/dashboard', [
            'stats' => [
                'draft' => (int) ($toStats[TravelOrderStatus::Draft->value] ?? 0),
                'submitted' => (int) ($toStats[TravelOrderStatus::Submitted->value] ?? 0),
                'approved' => (int) ($toStats[TravelOrderStatus::Approved->value] ?? 0),
                'completed' => (int) ($toStats[TravelOrderStatus::Completed->value] ?? 0),
                'cancelled' => (int) ($toStats[TravelOrderStatus::Cancelled->value] ?? 0),
            ],
            'fuelAlerts' => $alerts,
            'fuelThresholds' => [
                'low_balance_liters' => $lowLiters,
                'low_balance_amount' => $lowAmount,
            ],
        ]);
    }
}
