<?php

namespace App\Repositories\Eloquent;

use App\Models\FuelType;
use App\Repositories\Contracts\FuelTypeRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentFuelTypeRepository implements FuelTypeRepository
{
    /**
     * @return LengthAwarePaginator<int, FuelType>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = FuelType::query()
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            })
            ->withCount(['tripTickets', 'fuelAllocations'])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->each(function (FuelType $fuelType): void {
            $fuelType->setAttribute('usage_count', $fuelType->trip_tickets_count + $fuelType->fuel_allocations_count);
        });

        return $paginator;
    }

    public function find(int $id): ?FuelType
    {
        return FuelType::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FuelType
    {
        return FuelType::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FuelType $fuelType, array $data): FuelType
    {
        $fuelType->update($data);

        return $fuelType->fresh() ?? $fuelType;
    }

    public function delete(FuelType $fuelType): void
    {
        $fuelType->delete();
    }
}
