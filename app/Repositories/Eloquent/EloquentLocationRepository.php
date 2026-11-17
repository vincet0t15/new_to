<?php

namespace App\Repositories\Eloquent;

use App\Models\Location;
use App\Repositories\Contracts\LocationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentLocationRepository implements LocationRepository
{
    /**
     * @return LengthAwarePaginator<int, Location>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = Location::query()
            ->withCount([
                'transportationFares',
                'perDiemConfigurations',
                'originTravelOrders',
                'destinationTravelOrders',
                'officialStationTravelOrders',
            ])
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->each(function (Location $location): void {
            $location->setAttribute(
                'usage_count',
                $location->transportation_fares_count
                    + $location->per_diem_configurations_count
                    + $location->origin_travel_orders_count
                    + $location->destination_travel_orders_count
                    + $location->official_station_travel_orders_count,
            );
            unset(
                $location->transportation_fares_count,
                $location->per_diem_configurations_count,
                $location->origin_travel_orders_count,
                $location->destination_travel_orders_count,
                $location->official_station_travel_orders_count,
            );
        });

        return $paginator;
    }

    public function find(int $id): ?Location
    {
        return Location::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Location
    {
        return Location::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Location $location, array $data): Location
    {
        $location->update($data);

        return $location;
    }

    public function usageCount(Location $location): int
    {
        return $location->transportationFares()->count()
            + $location->perDiemConfigurations()->count()
            + $location->originTravelOrders()->count()
            + $location->destinationTravelOrders()->count()
            + $location->officialStationTravelOrders()->count();
    }

    public function delete(Location $location): void
    {
        $location->delete();
    }
}
