<?php

namespace App\Repositories\Eloquent;

use App\Models\TransportationMode;
use App\Repositories\Contracts\TransportationModeRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTransportationModeRepository implements TransportationModeRepository
{
    /**
     * @return LengthAwarePaginator<int, TransportationMode>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return TransportationMode::query()
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
        return 0;
    }
}
