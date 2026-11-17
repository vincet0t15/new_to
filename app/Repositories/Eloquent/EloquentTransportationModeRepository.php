<?php

namespace App\Repositories\Eloquent;

use App\Models\TransportationMode;
use App\Repositories\Contracts\TransportationModeRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentTransportationModeRepository implements TransportationModeRepository
{
    /**
     * @return LengthAwarePaginator<int, TransportationMode>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return TransportationMode::query()
            ->select('transportation_modes.*')
            ->selectSub(
                DB::table('travel_order_transportation_mode')
                    ->selectRaw('count(*)')
                    ->whereColumn('transportation_mode_id', 'transportation_modes.id'),
                'usage_count',
            )
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?TransportationMode
    {
        return TransportationMode::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TransportationMode
    {
        return TransportationMode::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TransportationMode $mode, array $data): TransportationMode
    {
        $mode->update($data);

        return $mode;
    }

    public function delete(TransportationMode $mode): void
    {
        $mode->delete();
    }

    public function usageCount(TransportationMode $mode): int
    {
        return $mode->travelOrders()->count();
    }
}
