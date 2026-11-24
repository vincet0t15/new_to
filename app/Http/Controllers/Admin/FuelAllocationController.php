<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ReadsAggregateRows;
use App\Http\Controllers\Controller;
use App\Http\Requests\FuelAllocations\SaveFuelAllocationRequest;
use App\Http\Requests\FuelAllocations\VoidFuelAllocationRequest;
use App\Models\FuelAllocation;
use App\Models\FuelAllocationEvent;
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
    use ReadsAggregateRows;

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'office_id' => $request->string('office_id')->toString(),
            'fuel_type_id' => $request->string('fuel_type_id')->toString(),
            'status' => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $allocations = FuelAllocation::query()
            ->with(['office:id,office_name', 'sourceOffice:id,office_name', 'creator:id,name', 'fuelTypeRecord:id,name', 'events.actor:id,name'])
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_no', 'like', $search)
                        ->orWhere('remarks', 'like', $search)
                        ->orWhere('fuel_type', 'like', $search)
                        ->orWhereHas('fuelTypeRecord', fn ($q) => $q->where('name', 'like', $search))
                        ->orWhereHas('office', fn ($office) => $office->where('office_name', 'like', $search))
                        ->orWhereHas('sourceOffice', fn ($office) => $office->where('office_name', 'like', $search));
                });
            })
            ->when($filters['office_id'] !== '', fn ($query) => $query->where('office_id', (int) $filters['office_id']))
            ->when($filters['fuel_type_id'] !== '', fn ($query) => $query->where('fuel_type_id', (int) $filters['fuel_type_id']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('allocation_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('allocation_date', '<=', $filters['date_to']))
            ->orderByDesc('allocation_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $officeBalances = $this->officeBalances();

        $prefix = $request->routeIs('admin.*') ? 'admin' : 'approvals';

        return Inertia::render('admin/fuel-allocations/index', [
            'allocations' => $allocations,
            'offices' => Office::query()->orderBy('office_name')->get(['id', 'office_name']),
            'fuelTypes' => FuelType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'officeBalances' => $officeBalances,
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
            $warning = $this->transferWarning($data);
            $this->storeTransfer($data, $request);

            return back()->with([
                'success' => 'Fuel transfer recorded.',
                'warning' => $warning,
            ]);
        }

        $data['source_office_id'] = null;
        $data['related_allocation_id'] = null;
        $allocation = FuelAllocation::query()->create($data);
        $this->logEvent($allocation, 'created', $request->user()?->id, null, [
            'allocated_liters' => $allocation->allocated_liters,
            'allocated_amount' => $allocation->allocated_amount,
            'fuel_type' => $allocation->fuel_type,
        ]);

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

        $before = $fuelAllocation->only(['office_id', 'allocation_date', 'reference_no', 'fuel_type_id', 'fuel_type', 'allocated_liters', 'allocated_amount', 'remarks']);
        $fuelAllocation->update($data);
        $this->logEvent($fuelAllocation, 'updated', $request->user()?->id, null, [
            'before' => $before,
            'after' => $fuelAllocation->only(array_keys($before)),
        ]);

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
        $this->logEvent($fuelAllocation, 'voided', $userId, $reason);

        $related = $fuelAllocation->relatedAllocation;
        if (! $related instanceof FuelAllocation && $fuelAllocation->related_allocation_id) {
            $related = FuelAllocation::query()->find($fuelAllocation->related_allocation_id);
        }
        if ($related instanceof FuelAllocation && $related->isActive()) {
            $relatedReason = 'Related transfer voided: '.$reason;
            $related->forceFill([
                'status' => FuelAllocation::STATUS_VOIDED,
                'void_reason' => $relatedReason,
                'voided_at' => $now,
                'updated_by' => $userId,
            ])->save();
            $this->logEvent($related, 'voided', $userId, $relatedReason, ['related_to' => $fuelAllocation->id]);
        }

        return back()->with('success', $fuelAllocation->type === FuelAllocation::TYPE_TRANSFER ? 'Fuel transfer voided.' : 'Fuel allocation voided.');
    }

    /**
     * @return array<int, array{office_id:int,fuel_type_id:int|null,remaining_liters:float,remaining_amount:float}>
     */
    private function officeBalances(): array
    {
        $allocated = FuelAllocation::query()
            ->where('status', FuelAllocation::STATUS_ACTIVE)
            ->selectRaw('office_id, fuel_type_id, COALESCE(SUM(allocated_liters),0) as liters, COALESCE(SUM(allocated_amount),0) as amount')
            ->groupBy('office_id', 'fuel_type_id')
            ->get();

        $consumed = DB::table('trip_tickets')
            ->join('travel_orders', 'travel_orders.id', '=', 'trip_tickets.travel_order_id')
            ->where('trip_tickets.status', 'completed')
            ->selectRaw('COALESCE(trip_tickets.fuel_source_office_id, travel_orders.office_id) as office_id, trip_tickets.fuel_type_id, COALESCE(SUM(COALESCE(trip_tickets.fuel_consumed,0)),0) as liters, COALESCE(SUM(COALESCE(trip_tickets.fuel_cost,0)),0) as amount')
            ->groupByRaw('COALESCE(trip_tickets.fuel_source_office_id, travel_orders.office_id), trip_tickets.fuel_type_id')
            ->get();

        $consumedByKey = [];
        foreach ($consumed as $row) {
            $key = $this->rawOfficeKey($row).'|'.$this->rawTypeKey($row, 'fuel_type_id');
            $consumedByKey[$key] = $row;
        }

        $balances = [];
        foreach ($allocated as $row) {
            $key = $this->rawOfficeKey($row).'|'.$this->rawTypeKey($row, 'fuel_type_id');
            $consumption = $consumedByKey[$key] ?? null;
            $liters = $this->rawFloat(data_get($row, 'liters'));
            $amount = $this->rawFloat(data_get($row, 'amount'));

            $balances[] = [
                'office_id' => $this->rawInt(data_get($row, 'office_id')),
                'fuel_type_id' => $this->rawNullableInt(data_get($row, 'fuel_type_id')),
                'remaining_liters' => $liters - $this->rawFloat(data_get($consumption, 'liters')),
                'remaining_amount' => $amount - $this->rawFloat(data_get($consumption, 'amount')),
            ];
        }

        return $balances;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function logEvent(FuelAllocation $allocation, string $event, ?int $actorUserId, ?string $remarks = null, array $meta = []): void
    {
        FuelAllocationEvent::query()->create([
            'fuel_allocation_id' => $allocation->id,
            'actor_user_id' => $actorUserId,
            'event' => $event,
            'remarks' => $remarks,
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function transferWarning(array $data): ?string
    {
        $sourceOfficeId = (int) ($data['source_office_id'] ?? 0);
        $fuelTypeId = $data['fuel_type_id'] ?? null;
        $liters = (float) ($data['allocated_liters'] ?? 0);
        $amount = (float) ($data['allocated_amount'] ?? 0);

        if ($sourceOfficeId <= 0 || ! $fuelTypeId) {
            return null;
        }

        $allocated = FuelAllocation::query()
            ->where('office_id', $sourceOfficeId)
            ->where('status', FuelAllocation::STATUS_ACTIVE)
            ->where('fuel_type_id', $fuelTypeId)
            ->selectRaw('COALESCE(SUM(allocated_liters),0) as liters, COALESCE(SUM(allocated_amount),0) as amount')
            ->first();
        $consumed = DB::table('trip_tickets')
            ->join('travel_orders', 'travel_orders.id', '=', 'trip_tickets.travel_order_id')
            ->where('trip_tickets.status', 'completed')
            ->where('trip_tickets.fuel_type_id', $fuelTypeId)
            ->whereRaw('COALESCE(trip_tickets.fuel_source_office_id, travel_orders.office_id) = ?', [$sourceOfficeId])
            ->selectRaw('COALESCE(SUM(COALESCE(trip_tickets.fuel_consumed,0)),0) as liters, COALESCE(SUM(COALESCE(trip_tickets.fuel_cost,0)),0) as amount')
            ->first();

        $remainingLiters = $this->rawFloat(data_get($allocated, 'liters')) - $this->rawFloat(data_get($consumed, 'liters'));
        $remainingAmount = $this->rawFloat(data_get($allocated, 'amount')) - $this->rawFloat(data_get($consumed, 'amount'));

        if (($liters > 0 && $remainingLiters < $liters) || ($amount > 0 && $remainingAmount < $amount)) {
            return sprintf(
                'Warning: source office remaining balance for this fuel type may be insufficient (Remaining: %.2f L / PHP %.2f). Transfer was still recorded as requested.',
                $remainingLiters,
                $remainingAmount,
            );
        }

        return null;
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
            $this->logEvent($debit, 'transfer_debit', $userId, null, ['related_allocation_id' => $credit->id, 'to_office_id' => $targetOfficeId, 'liters' => -$liters, 'amount' => -$amount]);
            $this->logEvent($credit, 'transfer_credit', $userId, null, ['related_allocation_id' => $debit->id, 'from_office_id' => $sourceOfficeId, 'liters' => $liters, 'amount' => $amount]);
        });
    }
}
