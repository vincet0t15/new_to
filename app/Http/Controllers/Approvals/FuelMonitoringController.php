<?php

namespace App\Http\Controllers\Approvals;

use App\Enums\TripTicketStatus;
use App\Http\Controllers\Controller;
use App\Models\FuelAllocation;
use App\Models\FuelType;
use App\Models\Office;
use App\Models\TripTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FuelMonitoringController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $base = $this->filteredQuery($filters);

        $completed = TripTicketStatus::Completed->value;
        $cancelled = TripTicketStatus::Cancelled->value;
        $officeExpr = 'COALESCE(trip_tickets.fuel_source_office_id, travel_orders.office_id)';
        $fuelTypeExpr = "COALESCE(NULLIF(fuel_types.name, ''), NULLIF(trip_tickets.fuel_type, ''), 'Unspecified')";

        $summary = (clone $base)
            ->selectRaw('COUNT(*) as total_tickets')
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status != '{$cancelled}' THEN trip_tickets.liters ELSE 0 END), 0) as estimated_liters")
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status = '{$completed}' THEN COALESCE(trip_tickets.fuel_consumed, 0) ELSE 0 END), 0) as actual_liters")
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status = '{$completed}' THEN COALESCE(trip_tickets.fuel_cost, 0) ELSE 0 END), 0) as fuel_cost")
            ->first();

        $usageByOffice = (clone $base)
            ->join('offices', 'offices.id', '=', DB::raw($officeExpr))
            ->selectRaw('offices.id as office_id')
            ->selectRaw('offices.office_name')
            ->selectRaw("{$fuelTypeExpr} as fuel_type")
            ->selectRaw('COUNT(trip_tickets.id) as total_tickets')
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status != '{$cancelled}' THEN trip_tickets.liters ELSE 0 END), 0) as estimated_liters")
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status = '{$completed}' THEN COALESCE(trip_tickets.fuel_consumed, 0) ELSE 0 END), 0) as actual_liters")
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status = '{$completed}' THEN COALESCE(trip_tickets.fuel_cost, 0) ELSE 0 END), 0) as fuel_cost")
            ->groupBy('offices.id', 'offices.office_name', DB::raw($fuelTypeExpr))
            ->get();

        $allocationsByOffice = FuelAllocation::query()
            ->join('offices', 'offices.id', '=', 'fuel_allocations.office_id')
            ->leftJoin('fuel_types as allocation_fuel_types', 'allocation_fuel_types.id', '=', 'fuel_allocations.fuel_type_id')
            ->where('fuel_allocations.status', FuelAllocation::STATUS_ACTIVE)
            ->when($filters['office_id'] !== '', fn (Builder $query): Builder => $query->where('fuel_allocations.office_id', (int) $filters['office_id']))
            ->when($filters['fuel_type_id'] !== '', fn (Builder $query): Builder => $query->where('fuel_allocations.fuel_type_id', (int) $filters['fuel_type_id']))
            ->selectRaw('offices.id as office_id')
            ->selectRaw('offices.office_name')
            ->selectRaw("COALESCE(NULLIF(allocation_fuel_types.name, ''), NULLIF(fuel_allocations.fuel_type, ''), 'Unspecified') as fuel_type")
            ->selectRaw('COALESCE(SUM(fuel_allocations.allocated_liters), 0) as allocated_liters')
            ->selectRaw('COALESCE(SUM(fuel_allocations.allocated_amount), 0) as allocated_amount')
            ->groupBy('offices.id', 'offices.office_name', DB::raw("COALESCE(NULLIF(allocation_fuel_types.name, ''), NULLIF(fuel_allocations.fuel_type, ''), 'Unspecified')"))
            ->get();

        $usageByKey = $usageByOffice->keyBy(fn ($row): string => $row->office_id.'|'.$row->fuel_type);
        $allocationsByKey = $allocationsByOffice->keyBy(fn ($row): string => $row->office_id.'|'.$row->fuel_type);
        $keys = $usageByKey->keys()->merge($allocationsByKey->keys())->unique();

        $byOfficeFuelType = $keys->map(function (string $key) use ($usageByKey, $allocationsByKey) {
            $usage = $usageByKey->get($key);
            $allocation = $allocationsByKey->get($key);
            $allocatedLiters = (float) ($allocation?->allocated_liters ?? 0);
            $allocatedAmount = (float) ($allocation?->allocated_amount ?? 0);
            $actualLiters = (float) ($usage?->actual_liters ?? 0);
            $actualCost = (float) ($usage?->fuel_cost ?? 0);

            return [
                'office_id' => (int) ($allocation?->office_id ?? $usage?->office_id),
                'office_name' => $allocation?->office_name ?? $usage?->office_name,
                'fuel_type' => $allocation?->fuel_type ?? $usage?->fuel_type ?? 'Unspecified',
                'total_tickets' => (int) ($usage?->total_tickets ?? 0),
                'estimated_liters' => (float) ($usage?->estimated_liters ?? 0),
                'actual_liters' => $actualLiters,
                'fuel_cost' => $actualCost,
                'allocated_liters' => $allocatedLiters,
                'allocated_amount' => $allocatedAmount,
                'remaining_liters' => $allocatedLiters - $actualLiters,
                'remaining_amount' => $allocatedAmount - $actualCost,
            ];
        })->sortBy([['office_name', 'asc'], ['fuel_type', 'asc']])->values();

        $byOffice = $byOfficeFuelType
            ->groupBy('office_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'office_id' => (int) $first['office_id'],
                    'office_name' => $first['office_name'],
                    'total_tickets' => (int) $rows->sum('total_tickets'),
                    'estimated_liters' => (float) $rows->sum('estimated_liters'),
                    'actual_liters' => (float) $rows->sum('actual_liters'),
                    'fuel_cost' => (float) $rows->sum('fuel_cost'),
                    'allocated_liters' => (float) $rows->sum('allocated_liters'),
                    'allocated_amount' => (float) $rows->sum('allocated_amount'),
                    'remaining_liters' => (float) $rows->sum('remaining_liters'),
                    'remaining_amount' => (float) $rows->sum('remaining_amount'),
                    'fuel_types' => $rows->values()->all(),
                ];
            })
            ->sortByDesc('fuel_cost')
            ->values();

        $borrowedByOffice = (clone $base)
            ->whereNotNull('trip_tickets.fuel_source_office_id')
            ->whereColumn('trip_tickets.fuel_source_office_id', '!=', 'travel_orders.office_id')
            ->join('offices as borrower_offices', 'borrower_offices.id', '=', 'travel_orders.office_id')
            ->join('offices as source_offices', 'source_offices.id', '=', 'trip_tickets.fuel_source_office_id')
            ->selectRaw('borrower_offices.office_name as borrower_office_name')
            ->selectRaw('source_offices.office_name as source_office_name')
            ->selectRaw("{$fuelTypeExpr} as fuel_type")
            ->selectRaw('COUNT(trip_tickets.id) as total_tickets')
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status != '{$cancelled}' THEN trip_tickets.liters ELSE 0 END), 0) as estimated_liters")
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status = '{$completed}' THEN COALESCE(trip_tickets.fuel_consumed, 0) ELSE 0 END), 0) as actual_liters")
            ->selectRaw("COALESCE(SUM(CASE WHEN trip_tickets.status = '{$completed}' THEN COALESCE(trip_tickets.fuel_cost, 0) ELSE 0 END), 0) as fuel_cost")
            ->groupBy('borrower_offices.office_name', 'source_offices.office_name', DB::raw($fuelTypeExpr))
            ->orderByDesc('fuel_cost')
            ->get();

        $tickets = (clone $base)
            ->select('trip_tickets.*')
            ->with([
                'travelOrder:id,control_no,to_number,purpose,office_id',
                'travelOrder.office:id,office_name',
                'fuelSourceOffice:id,office_name',
                'fuelTypeRecord:id,name',
                'driver:id,first_name,middle_name,last_name,suffix',
            ])
            ->latest('trip_tickets.created_at')
            ->paginate(20)
            ->withQueryString();

        $prefix = $request->routeIs('admin.*') ? 'admin' : 'approvals';

        $allocationSummary = FuelAllocation::query()
            ->where('status', FuelAllocation::STATUS_ACTIVE)
            ->when($filters['office_id'] !== '', fn (Builder $query): Builder => $query->where('office_id', (int) $filters['office_id']))
            ->when($filters['fuel_type_id'] !== '', fn (Builder $query): Builder => $query->where('fuel_type_id', (int) $filters['fuel_type_id']))
            ->selectRaw('COALESCE(SUM(allocated_liters), 0) as allocated_liters')
            ->selectRaw('COALESCE(SUM(allocated_amount), 0) as allocated_amount')
            ->first();

        return Inertia::render('approvals/fuel-monitoring/index', [
            'summary' => [
                'total_tickets' => (int) ($summary?->total_tickets ?? 0),
                'estimated_liters' => (float) ($summary?->estimated_liters ?? 0),
                'actual_liters' => (float) ($summary?->actual_liters ?? 0),
                'fuel_cost' => (float) ($summary?->fuel_cost ?? 0),
                'allocated_liters' => (float) ($allocationSummary?->allocated_liters ?? 0),
                'allocated_amount' => (float) ($allocationSummary?->allocated_amount ?? 0),
                'remaining_liters' => (float) ($allocationSummary?->allocated_liters ?? 0) - (float) ($summary?->actual_liters ?? 0),
                'remaining_amount' => (float) ($allocationSummary?->allocated_amount ?? 0) - (float) ($summary?->fuel_cost ?? 0),
            ],
            'byOffice' => $byOffice,
            'byOfficeFuelType' => $byOfficeFuelType,
            'borrowedByOffice' => $borrowedByOffice,
            'tickets' => $tickets,
            'offices' => Office::query()->orderBy('office_name')->get(['id', 'office_name']),
            'fuelTypes' => FuelType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'urls' => [
                'index' => route($prefix.'.fuel-monitoring.index'),
                'export' => route($prefix.'.fuel-monitoring.export'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters)
            ->with([
                'travelOrder:id,control_no,to_number,purpose,office_id',
                'travelOrder.office:id,office_name',
                'fuelSourceOffice:id,office_name',
                'fuelTypeRecord:id,name',
                'driver:id,first_name,middle_name,last_name,suffix',
            ])
            ->orderByDesc('trip_tickets.created_at');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fuel-monitoring-report.csv"',
        ];

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Ticket No',
                'Status',
                'Fuel Source Office',
                'Travel Order Office',
                'Borrowed',
                'Travel Order',
                'Purpose',
                'Vehicle Plate',
                'Vehicle Type',
                'Driver',
                'Fuel Type',
                'Distance (KM)',
                'Estimated Liters',
                'Actual Liters',
                'Fuel Price / Liter',
                'Fuel Cost',
                'Fuel Station',
                'Receipt No',
                'Receipt URL',
                'Created At',
            ]);

            $query->chunk(200, function ($tickets) use ($handle): void {
                foreach ($tickets as $ticket) {
                    $sourceOffice = $ticket->fuelSourceOffice?->office_name ?? $ticket->travelOrder?->office?->office_name;
                    $toOffice = $ticket->travelOrder?->office?->office_name;
                    fputcsv($handle, [
                        $ticket->ticket_no,
                        $ticket->status->value,
                        $sourceOffice,
                        $toOffice,
                        $sourceOffice && $toOffice && $sourceOffice !== $toOffice ? 'Yes' : 'No',
                        $ticket->travelOrder?->control_no ?? $ticket->travelOrder?->to_number ?? $ticket->travel_order_id,
                        $ticket->travelOrder?->purpose,
                        $ticket->vehicle_plate_no,
                        $ticket->vehicle_type,
                        $ticket->driver?->full_name,
                        $ticket->fuel_type,
                        $ticket->distance_km,
                        $ticket->liters,
                        $ticket->fuel_consumed,
                        $ticket->fuel_price_per_liter,
                        $ticket->fuel_cost,
                        $ticket->fuel_station,
                        $ticket->fuel_receipt_no,
                        $ticket->fuel_receipt_url,
                        $ticket->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, 'fuel-monitoring-report.csv', $headers);
    }

    /**
     * @return array{search: string, office_id: string, fuel_type_id: string, borrowed_only: string, date_from: string, date_to: string, status: string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'office_id' => $request->string('office_id')->toString(),
            'fuel_type_id' => $request->string('fuel_type_id')->toString(),
            'borrowed_only' => $request->boolean('borrowed_only') ? '1' : '',
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'status' => $request->string('status')->toString(),
        ];
    }

    /**
     * @param  array{search: string, office_id: string, fuel_type_id: string, borrowed_only: string, date_from: string, date_to: string, status: string}  $filters
     * @return Builder<TripTicket>
     */
    private function filteredQuery(array $filters): Builder
    {
        return TripTicket::query()
            ->join('travel_orders', 'travel_orders.id', '=', 'trip_tickets.travel_order_id')
            ->leftJoin('fuel_types', 'fuel_types.id', '=', 'trip_tickets.fuel_type_id')
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('trip_tickets.ticket_no', 'like', $search)
                        ->orWhere('trip_tickets.vehicle_plate_no', 'like', $search)
                        ->orWhere('trip_tickets.fuel_station', 'like', $search)
                        ->orWhere('trip_tickets.fuel_receipt_no', 'like', $search)
                        ->orWhere('trip_tickets.fuel_type', 'like', $search)
                        ->orWhere('fuel_types.name', 'like', $search)
                        ->orWhere('travel_orders.purpose', 'like', $search)
                        ->orWhere('travel_orders.control_no', 'like', $search)
                        ->orWhere('travel_orders.to_number', 'like', $search);
                });
            })
            ->when($filters['office_id'] !== '', fn (Builder $query): Builder => $query->whereRaw('COALESCE(trip_tickets.fuel_source_office_id, travel_orders.office_id) = ?', [(int) $filters['office_id']]))
            ->when($filters['fuel_type_id'] !== '', fn (Builder $query): Builder => $query->where('trip_tickets.fuel_type_id', (int) $filters['fuel_type_id']))
            ->when($filters['borrowed_only'] === '1', fn (Builder $query): Builder => $query->whereNotNull('trip_tickets.fuel_source_office_id')->whereColumn('trip_tickets.fuel_source_office_id', '!=', 'travel_orders.office_id'))
            ->when($filters['status'] !== '', fn (Builder $query): Builder => $query->where('trip_tickets.status', $filters['status']))
            ->when($filters['date_from'] !== '', fn (Builder $query): Builder => $query->whereDate('trip_tickets.created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $query): Builder => $query->whereDate('trip_tickets.created_at', '<=', $filters['date_to']));
    }
}
