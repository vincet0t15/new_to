<?php

namespace App\Repositories\Eloquent;

use App\Models\Vehicle;
use App\Repositories\Contracts\VehicleRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentVehicleRepository implements VehicleRepository
{
    /**
     * @return LengthAwarePaginator<int, Vehicle>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = Vehicle::query()
            ->with('driver')
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where('plate_no', 'like', '%'.$search.'%')
                    ->orWhere('vehicle_type', 'like', '%'.$search.'%')
                    ->orWhere('model', 'like', '%'.$search.'%')
                    ->orWhereHas('driver', fn ($query): mixed => $query
                        ->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%'));
            })
            ->withCount('tripTickets')
            ->orderBy('plate_no')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->each(function (Vehicle $vehicle): void {
            $vehicle->setAttribute('usage_count', $vehicle->trip_tickets_count);
        });

        return $paginator;
    }

    public function find(int $id): ?Vehicle
    {
        return Vehicle::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vehicle
    {
        return Vehicle::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->update($data);

        return $vehicle->fresh() ?? $vehicle;
    }

    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }
}
