<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FuelAllocations\SaveFuelAllocationRequest;
use App\Http\Requests\FuelAllocations\VoidFuelAllocationRequest;
use App\Models\FuelAllocation;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class FuelAllocationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'office_id' => $request->string('office_id')->toString(),
            'status' => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $allocations = FuelAllocation::query()
            ->with(['office:id,office_name', 'creator:id,name'])
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_no', 'like', $search)
                        ->orWhere('remarks', 'like', $search)
                        ->orWhere('fuel_type', 'like', $search)
                        ->orWhereHas('office', fn ($office) => $office->where('office_name', 'like', $search));
                });
            })
            ->when($filters['office_id'] !== '', fn ($query) => $query->where('office_id', (int) $filters['office_id']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('allocation_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('allocation_date', '<=', $filters['date_to']))
            ->orderByDesc('allocation_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $prefix = $request->routeIs('admin.*') ? 'admin' : 'approvals';

        return Inertia::render('admin/fuel-allocations/index', [
            'allocations' => $allocations,
            'offices' => Office::query()->orderBy('office_name')->get(['id', 'office_name']),
            'filters' => $filters,
            'urls' => [
                'index' => route($prefix.'.fuel-allocations.index'),
                'store' => route($prefix.'.fuel-allocations.store'),
                'update' => route($prefix.'.fuel-allocations.update', ['fuelAllocation' => '__ID__']),
                'void' => route($prefix.'.fuel-allocations.void', ['fuelAllocation' => '__ID__']),
            ],
        ]);
    }

    public function store(SaveFuelAllocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['allocated_liters'] = $data['allocated_liters'] ?? 0;
        $data['allocated_amount'] = $data['allocated_amount'] ?? 0;
        $data['status'] = FuelAllocation::STATUS_ACTIVE;
        $data['created_by'] = $request->user()?->id;

        FuelAllocation::query()->create($data);

        return back()->with('success', 'Fuel allocation added.');
    }

    public function update(SaveFuelAllocationRequest $request, FuelAllocation $fuelAllocation): RedirectResponse
    {
        if (! $fuelAllocation->isActive()) {
            return back()->withErrors(['status' => 'Voided allocations cannot be edited.']);
        }

        $data = $request->validated();
        $data['allocated_liters'] = $data['allocated_liters'] ?? 0;
        $data['allocated_amount'] = $data['allocated_amount'] ?? 0;
        $data['updated_by'] = $request->user()?->id;

        $fuelAllocation->update($data);

        return back()->with('success', 'Fuel allocation updated.');
    }

    public function void(VoidFuelAllocationRequest $request, FuelAllocation $fuelAllocation): RedirectResponse
    {
        if (! $fuelAllocation->isActive()) {
            return back()->withErrors(['status' => 'Allocation is already voided.']);
        }

        $fuelAllocation->forceFill([
            'status' => FuelAllocation::STATUS_VOIDED,
            'void_reason' => $request->validated('void_reason'),
            'voided_at' => Carbon::now(),
            'updated_by' => $request->user()?->id,
        ])->save();

        return back()->with('success', 'Fuel allocation voided.');
    }
}
