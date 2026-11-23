<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FuelAllocations\SaveFuelAllocationRequest;
use App\Http\Requests\FuelAllocations\VoidFuelAllocationRequest;
use App\Models\FuelAllocation;
use App\Models\FuelType;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            ->with(['office:id,office_name', 'sourceOffice:id,office_name', 'creator:id,name', 'fuelTypeRecord:id,name'])
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_no', 'like', $search)
                        ->orWhere('remarks', 'like', $search)
                        ->orWhere('fuel_type', 'like', $search)
                        ->orWhereHas('fuelType', fn ($q) => $q->where('name', 'like', $search))
                        ->orWhereHas('office', fn ($office) => $office->where('office_name', 'like', $search))
                        ->orWhereHas('sourceOffice', fn ($office) => $office->where('office_name', 'like', $search));
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
            'fuelTypes' => FuelType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'urls' => [
                'index' => route($prefix.'.fuel-allocations.index'),
                'store' => route($prefix.'.fuel-allocations.store'),
                'update' => route($prefix.'.fuel-allocations.update', ['fuelAllocation' => '__ID__']),
                'void' => route($prefix.'.fuel-allocations.void', ['fuelAllocation' => '__ID__']),
                'fuelMonitoring' => route($prefix.'.fuel-monitoring.index'),
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
        if (! empty($data['fuel_type_id'])) {
            $data['fuel_type'] = FuelType::query()->whereKey($data['fuel_type_id'])->value('name') ?? ($data['fuel_type'] ?? null);
        }

        if (($data['type'] ?? FuelAllocation::TYPE_ALLOCATION) === FuelAllocation::TYPE_TRANSFER) {
            $this->storeTransfer($data, $request);

            return back()->with('success', 'Fuel transfer recorded.');
        }

        $data['source_office_id'] = null;
        $data['related_allocation_id'] = null;
        FuelAllocation::query()->create($data);

        return back()->with('success', 'Fuel allocation added.');
    }

    public function update(SaveFuelAllocationRequest $request, FuelAllocation $fuelAllocation): RedirectResponse
    {
        if (! $fuelAllocation->isActive()) {
            return back()->withErrors(['status' => 'Voided allocations cannot be edited.']);
        }

        if ($fuelAllocation->type === FuelAllocation::TYPE_TRANSFER) {
            return back()->withErrors(['status' => 'Transfer records cannot be edited. Void and recreate the transfer instead.']);
        }

        $data = $request->validated();
        $data['allocated_liters'] = $data['allocated_liters'] ?? 0;
        $data['allocated_amount'] = $data['allocated_amount'] ?? 0;
        $data['updated_by'] = $request->user()?->id;
        if (! empty($data['fuel_type_id'])) {
            $data['fuel_type'] = FuelType::query()->whereKey($data['fuel_type_id'])->value('name') ?? ($data['fuel_type'] ?? null);
        }
        $data['type'] = FuelAllocation::TYPE_ALLOCATION;
        $data['source_office_id'] = null;
        $data['related_allocation_id'] = null;

        $fuelAllocation->update($data);

        return back()->with('success', 'Fuel allocation updated.');
    }

    public function void(VoidFuelAllocationRequest $request, FuelAllocation $fuelAllocation): RedirectResponse
    {
        if (! $fuelAllocation->isActive()) {
            return back()->withErrors(['status' => 'Allocation is already voided.']);
        }

        $reason = $request->validated('void_reason');
        $now = Carbon::now();
        $userId = $request->user()?->id;

        $fuelAllocation->forceFill([
            'status' => FuelAllocation::STATUS_VOIDED,
            'void_reason' => $reason,
            'voided_at' => $now,
            'updated_by' => $userId,
        ])->save();

        $related = $fuelAllocation->relatedAllocation;
        if (! $related instanceof FuelAllocation && $fuelAllocation->related_allocation_id) {
            $related = FuelAllocation::query()->find($fuelAllocation->related_allocation_id);
        }
        if ($related instanceof FuelAllocation && $related->isActive()) {
            $related->forceFill([
                'status' => FuelAllocation::STATUS_VOIDED,
                'void_reason' => 'Related transfer voided: '.$reason,
                'voided_at' => $now,
                'updated_by' => $userId,
            ])->save();
        }

        return back()->with('success', $fuelAllocation->type === FuelAllocation::TYPE_TRANSFER ? 'Fuel transfer voided.' : 'Fuel allocation voided.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeTransfer(array $data, Request $request): void
    {
        $userId = $request->user()?->id;
        $liters = (float) ($data['allocated_liters'] ?? 0);
        $amount = (float) ($data['allocated_amount'] ?? 0);
        $sourceOfficeId = (int) $data['source_office_id'];
        $targetOfficeId = (int) $data['office_id'];
        $remarks = trim((string) ($data['remarks'] ?? ''));
        $fuelTypeId = $data['fuel_type_id'] ?? null;
        $fuelTypeName = $data['fuel_type'] ?? null;
        if ($fuelTypeId) {
            $fuelTypeName = FuelType::query()->whereKey($fuelTypeId)->value('name') ?? $fuelTypeName;
        }

        DB::transaction(function () use ($data, $userId, $liters, $amount, $sourceOfficeId, $targetOfficeId, $remarks, $fuelTypeId, $fuelTypeName): void {
            $debit = FuelAllocation::query()->create([
                'type' => FuelAllocation::TYPE_TRANSFER,
                'office_id' => $sourceOfficeId,
                'source_office_id' => $sourceOfficeId,
                'allocation_date' => $data['allocation_date'],
                'reference_no' => $data['reference_no'] ?? null,
                'fuel_type_id' => $fuelTypeId,
                'fuel_type' => $fuelTypeName,
                'allocated_liters' => -$liters,
                'allocated_amount' => -$amount,
                'remarks' => trim('Transferred to office ID '.$targetOfficeId.($remarks !== '' ? '. '.$remarks : '')),
                'status' => FuelAllocation::STATUS_ACTIVE,
                'created_by' => $userId,
            ]);

            $credit = FuelAllocation::query()->create([
                'type' => FuelAllocation::TYPE_TRANSFER,
                'office_id' => $targetOfficeId,
                'source_office_id' => $sourceOfficeId,
                'related_allocation_id' => $debit->id,
                'allocation_date' => $data['allocation_date'],
                'reference_no' => $data['reference_no'] ?? null,
                'fuel_type_id' => $fuelTypeId,
                'fuel_type' => $fuelTypeName,
                'allocated_liters' => $liters,
                'allocated_amount' => $amount,
                'remarks' => trim('Borrowed from office ID '.$sourceOfficeId.($remarks !== '' ? '. '.$remarks : '')),
                'status' => FuelAllocation::STATUS_ACTIVE,
                'created_by' => $userId,
            ]);

            $debit->forceFill(['related_allocation_id' => $credit->id])->save();
        });
    }
}
