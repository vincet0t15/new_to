<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ReadsAggregateRows;
use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\FuelAllocation;
use App\Models\TravelOrder;
use App\Repositories\Contracts\TravelOrderRepository;
use App\Services\TravelOrderApprovalService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ReadsAggregateRows;

    public function __construct(
        private readonly TravelOrderRepository $travelOrders,
        private readonly TravelOrderApprovalService $approvals,
    ) {
        //
    }

    public function __invoke(): Response
    {
        $lowLiters = (float) config('travel-orders.fuel.low_balance_liters', 20);
        $lowAmount = (float) config('travel-orders.fuel.low_balance_amount', 1000);

        // Resolve the label in a derived table and group by plain columns.
        // MySQL only guarantees that a non-column GROUP BY expression matches
        // an identical SELECT expression on newer versions; stricter builds
        // still reject the raw fuel_types.name reference under
        // ONLY_FULL_GROUP_BY.
        $allocationSource = DB::table('fuel_allocations')
            ->join('offices', 'offices.id', '=', 'fuel_allocations.office_id')
            ->leftJoin('fuel_types', 'fuel_types.id', '=', 'fuel_allocations.fuel_type_id')
            ->where('fuel_allocations.status', FuelAllocation::STATUS_ACTIVE)
            ->select([
                'offices.id as office_id',
                'offices.office_name',
                'fuel_allocations.fuel_type_id',
                DB::raw("COALESCE(NULLIF(fuel_types.name, ''), NULLIF(fuel_allocations.fuel_type, ''), 'Unspecified') as resolved_fuel_type"),
                'fuel_allocations.allocated_liters',
                'fuel_allocations.allocated_amount',
            ]);

        $allocated = DB::query()
            ->fromSub($allocationSource, 'allocation_rows')
            ->selectRaw('office_id, office_name, fuel_type_id, resolved_fuel_type as fuel_type')
            ->selectRaw('COALESCE(SUM(allocated_liters),0) as allocated_liters')
            ->selectRaw('COALESCE(SUM(allocated_amount),0) as allocated_amount')
            ->groupBy('office_id', 'office_name', 'fuel_type_id', 'resolved_fuel_type')
            ->get();

        $consumptionSource = DB::table('trip_tickets')
            ->join('travel_orders', 'travel_orders.id', '=', 'trip_tickets.travel_order_id')
            ->leftJoin('fuel_types', 'fuel_types.id', '=', 'trip_tickets.fuel_type_id')
            ->where('trip_tickets.status', 'completed')
            ->select([
                DB::raw('COALESCE(trip_tickets.fuel_source_office_id, travel_orders.office_id) as office_id'),
                'trip_tickets.fuel_type_id',
                DB::raw("COALESCE(NULLIF(fuel_types.name, ''), NULLIF(trip_tickets.fuel_type, ''), 'Unspecified') as resolved_fuel_type"),
                'trip_tickets.fuel_consumed',
                'trip_tickets.fuel_cost',
            ]);

        $consumed = DB::query()
            ->fromSub($consumptionSource, 'completed_trips')
            ->selectRaw('office_id, fuel_type_id, resolved_fuel_type as fuel_type')
            ->selectRaw('COALESCE(SUM(COALESCE(fuel_consumed,0)),0) as actual_liters')
            ->selectRaw('COALESCE(SUM(COALESCE(fuel_cost,0)),0) as fuel_cost')
            ->groupBy('office_id', 'fuel_type_id', 'resolved_fuel_type')
            ->get();

        $consumedByKey = [];
        foreach ($consumed as $row) {
            $key = $this->rawOfficeKey($row)
                .'|'.$this->rawTypeKey($row, 'fuel_type_id')
                .'|'.$this->rawTypeKey($row, 'fuel_type', 'Unspecified');
            $consumedByKey[$key] = $row;
        }

        $fuelBalances = [];
        foreach ($allocated as $row) {
            $key = $this->rawOfficeKey($row)
                .'|'.$this->rawTypeKey($row, 'fuel_type_id')
                .'|'.$this->rawTypeKey($row, 'fuel_type', 'Unspecified');
            $consumption = $consumedByKey[$key] ?? null;
            $allocatedLiters = $this->rawFloat(data_get($row, 'allocated_liters'));
            $allocatedAmount = $this->rawFloat(data_get($row, 'allocated_amount'));
            $actualLiters = $this->rawFloat(data_get($consumption, 'actual_liters'));
            $fuelCost = $this->rawFloat(data_get($consumption, 'fuel_cost'));

            $fuelBalances[] = [
                'office_id' => $this->rawInt(data_get($row, 'office_id')),
                'office_name' => $this->rawText(data_get($row, 'office_name')),
                'fuel_type' => $this->rawText(data_get($row, 'fuel_type'), 'Unspecified'),
                'allocated_liters' => $allocatedLiters,
                'allocated_amount' => $allocatedAmount,
                'actual_liters' => $actualLiters,
                'fuel_cost' => $fuelCost,
                'remaining_liters' => $allocatedLiters - $actualLiters,
                'remaining_amount' => $allocatedAmount - $fuelCost,
            ];
        }

        $alerts = array_values(array_filter(
            $fuelBalances,
            static fn (array $row): bool => $row['remaining_liters'] < 0
                || $row['remaining_amount'] < 0
                || $row['remaining_liters'] <= $lowLiters
                || $row['remaining_amount'] <= $lowAmount,
        ));
        usort($alerts, static function (array $left, array $right): int {
            return [$left['remaining_liters'], $left['remaining_amount']]
                <=> [$right['remaining_liters'], $right['remaining_amount']];
        });
        $alerts = array_slice($alerts, 0, 8);

        $toStats = TravelOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentOrders = TravelOrder::query()
            ->with(['office:id,office_name', 'employee:id,first_name,middle_name,last_name,suffix'])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (TravelOrder $order): array => [
                'id' => $order->id,
                'control_no' => $order->control_no,
                'to_number' => $order->to_number,
                'purpose' => $order->purpose,
                'status' => $order->status->value,
                'employee' => $order->employee?->full_name,
                'office' => $order->office?->office_name,
                'updated_at' => $order->updated_at?->toIso8601String(),
            ])
            ->values();

        $pendingFuelTickets = (int) DB::table('trip_tickets')
            ->where('status', 'issued')
            ->count();
        $completedFuelTickets = (int) DB::table('trip_tickets')
            ->where('status', 'completed')
            ->count();
        $activeAllocations = (int) FuelAllocation::query()
            ->where('status', FuelAllocation::STATUS_ACTIVE)
            ->count();

        $stalledAfterDays = max(1, (int) config('travel-orders.approval.stalled_after_days', 3));
        $stalledOrders = $this->travelOrders
            ->stalledPending(8, $stalledAfterDays)
            ->map(function (TravelOrder $order): array {
                return [
                    'id' => $order->id,
                    'control_no' => $order->control_no,
                    'to_number' => $order->to_number,
                    'purpose' => $order->purpose,
                    'submitted_at' => $order->submitted_at?->toIso8601String(),
                    'age_days' => (int) abs($order->submitted_at?->diffInDays(now()) ?? 0),
                    'holder' => $order->recipient_user_id !== null
                        ? $order->recipient->name
                        : __('Unassigned'),
                    'step' => $this->approvals->currentStep($order)?->displayLabel() ?? '—',
                    'employee' => $order->employee?->full_name,
                    'office' => $order->office?->office_name,
                ];
            })
            ->values();
        $stalledTotal = $this->travelOrders->stalledPendingCount($stalledAfterDays);

        return Inertia::render('portals/admin/dashboard', [
            'stats' => [
                'draft' => (int) ($toStats[TravelOrderStatus::Draft->value] ?? 0),
                'submitted' => (int) ($toStats[TravelOrderStatus::Submitted->value] ?? 0),
                'returned' => (int) ($toStats[TravelOrderStatus::Returned->value] ?? 0),
                'approved' => (int) ($toStats[TravelOrderStatus::Approved->value] ?? 0),
                'completed' => (int) ($toStats[TravelOrderStatus::Completed->value] ?? 0),
                'cancelled' => (int) ($toStats[TravelOrderStatus::Cancelled->value] ?? 0),
                'rejected' => (int) ($toStats[TravelOrderStatus::Rejected->value] ?? 0),
            ],
            'fuelAlerts' => $alerts,
            'fuelThresholds' => [
                'low_balance_liters' => $lowLiters,
                'low_balance_amount' => $lowAmount,
            ],
            'recentOrders' => $recentOrders,
            'stalledOrders' => $stalledOrders,
            'stalledTotal' => $stalledTotal,
            'stalledThresholdDays' => $stalledAfterDays,
            'fuelSummary' => [
                'active_allocations' => $activeAllocations,
                'issued_trip_tickets' => $pendingFuelTickets,
                'completed_trip_tickets' => $completedFuelTickets,
            ],
        ]);
    }
}
