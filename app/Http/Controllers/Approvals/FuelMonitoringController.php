<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\TripTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FuelMonitoringController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $base = $this->filteredQuery($filters);

        $summary = (clone $base)
            ->selectRaw('COUNT(*) as total_tickets')
            ->selectRaw('COALESCE(SUM(liters), 0) as estimated_liters')
            ->selectRaw('COALESCE(SUM(fuel_consumed), 0) as actual_liters')
            ->selectRaw('COALESCE(SUM(fuel_cost), 0) as fuel_cost')
            ->first();

        $byOffice = (clone $base)
            ->join('offices', 'offices.id', '=', 'travel_orders.office_id')
            ->selectRaw('offices.office_name')
            ->selectRaw('COUNT(trip_tickets.id) as total_tickets')
            ->selectRaw('COALESCE(SUM(trip_tickets.liters), 0) as estimated_liters')
            ->selectRaw('COALESCE(SUM(trip_tickets.fuel_consumed), 0) as actual_liters')
            ->selectRaw('COALESCE(SUM(trip_tickets.fuel_cost), 0) as fuel_cost')
            ->groupBy('offices.office_name')
            ->orderByDesc('fuel_cost')
            ->orderByDesc('actual_liters')
            ->get();

        $tickets = (clone $base)
            ->select('trip_tickets.*')
            ->with([
                'travelOrder:id,control_no,to_number,purpose,office_id',
                'travelOrder.office:id,office_name',
                'driver:id,first_name,middle_name,last_name,suffix',
            ])
            ->latest('trip_tickets.created_at')
            ->paginate(20)
            ->withQueryString();

        $prefix = $request->routeIs('admin.*') ? 'admin' : 'approvals';

        return Inertia::render('approvals/fuel-monitoring/index', [
            'summary' => [
                'total_tickets' => (int) ($summary?->total_tickets ?? 0),
                'estimated_liters' => (float) ($summary?->estimated_liters ?? 0),
                'actual_liters' => (float) ($summary?->actual_liters ?? 0),
                'fuel_cost' => (float) ($summary?->fuel_cost ?? 0),
            ],
            'byOffice' => $byOffice,
            'tickets' => $tickets,
            'offices' => Office::query()->orderBy('office_name')->get(['id', 'office_name']),
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
                'Office',
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
                'Created At',
            ]);

            $query->chunk(200, function ($tickets) use ($handle): void {
                foreach ($tickets as $ticket) {
                    fputcsv($handle, [
                        $ticket->ticket_no,
                        $ticket->status->value,
                        $ticket->travelOrder?->office?->office_name,
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
                        $ticket->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, 'fuel-monitoring-report.csv', $headers);
    }

    /**
     * @return array{search: string, office_id: string, date_from: string, date_to: string, status: string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'office_id' => $request->string('office_id')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'status' => $request->string('status')->toString(),
        ];
    }

    /**
     * @param  array{search: string, office_id: string, date_from: string, date_to: string, status: string}  $filters
     * @return Builder<TripTicket>
     */
    private function filteredQuery(array $filters): Builder
    {
        return TripTicket::query()
            ->join('travel_orders', 'travel_orders.id', '=', 'trip_tickets.travel_order_id')
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('trip_tickets.ticket_no', 'like', $search)
                        ->orWhere('trip_tickets.vehicle_plate_no', 'like', $search)
                        ->orWhere('trip_tickets.fuel_station', 'like', $search)
                        ->orWhere('trip_tickets.fuel_receipt_no', 'like', $search)
                        ->orWhere('travel_orders.purpose', 'like', $search)
                        ->orWhere('travel_orders.control_no', 'like', $search)
                        ->orWhere('travel_orders.to_number', 'like', $search);
                });
            })
            ->when($filters['office_id'] !== '', fn (Builder $query): Builder => $query->where('travel_orders.office_id', (int) $filters['office_id']))
            ->when($filters['status'] !== '', fn (Builder $query): Builder => $query->where('trip_tickets.status', $filters['status']))
            ->when($filters['date_from'] !== '', fn (Builder $query): Builder => $query->whereDate('trip_tickets.created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $query): Builder => $query->whereDate('trip_tickets.created_at', '<=', $filters['date_to']));
    }
}
