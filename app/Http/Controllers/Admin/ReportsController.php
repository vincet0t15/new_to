<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\TravelOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $base = $this->filteredQuery($filters);

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byOffice = (clone $base)
            ->join('offices', 'offices.id', '=', 'travel_orders.office_id')
            ->selectRaw('offices.office_name, COUNT(*) as total')
            ->groupBy('offices.office_name')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('admin/reports/index', [
            'summary' => [
                'total' => (clone $base)->count(),
                'by_status' => $byStatus,
            ],
            'by_office' => $byOffice,
            'offices' => Office::query()->orderBy('office_name')->get(['id', 'office_name']),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters)
            ->with(['office:id,office_name', 'employee:id,first_name,last_name', 'creator:id,name'])
            ->orderBy('created_at');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="travel-orders-report.csv"',
        ];

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, [
                'ID', 'Control No.', 'TO Number', 'Status', 'Revision',
                'Employee', 'Office', 'Purpose', 'Destination',
                'Date From', 'Date To', 'Fund Source', 'Classification',
                'Created By', 'Created At',
            ]);

            $query->chunk(200, function ($orders) use ($handle) {
                /** @var Collection<int, TravelOrder> $orders */
                foreach ($orders as $order) {
                    /** @var TravelOrder $order */
                    fputcsv($handle, [
                        $order->id,
                        $order->control_no,
                        $order->to_number,
                        $order->status->value,
                        $order->revision_number,
                        $order->employee?->full_name,
                        $order->office?->office_name,
                        $order->purpose,
                        $order->destination,
                        $order->date_from?->toDateString(),
                        $order->date_to?->toDateString(),
                        $order->fund_source,
                        $order->travel_classification,
                        $order->creator->name,
                        $order->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, 'travel-orders-report.csv', $headers);
    }

    /**
     * @return array{search: string, status: string, office_id: string, date_from: string, date_to: string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'office_id' => $request->string('office_id')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];
    }

    /**
     * @param  array{search: string, status: string, office_id: string, date_from: string, date_to: string}  $filters
     * @return Builder<TravelOrder>
     */
    private function filteredQuery(array $filters): Builder
    {
        return TravelOrder::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('purpose', 'like', '%'.$filters['search'].'%')
                        ->orWhere('destination', 'like', '%'.$filters['search'].'%')
                        ->orWhere('to_number', 'like', '%'.$filters['search'].'%')
                        ->orWhere('control_no', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['office_id'] !== '', fn ($query) => $query->where('office_id', (int) $filters['office_id']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']));
    }
}
