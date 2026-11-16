<?php

namespace App\Repositories\Eloquent;

use App\Models\Office;
use App\Repositories\Contracts\OfficeRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentOfficeRepository implements OfficeRepository
{
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator
    {
        return Office::query()
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('office_name', 'like', '%'.$search.'%')
                        ->orWhere('office_code', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('office_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Office
    {
        return Office::create($data);
    }

    public function update(Office $office, array $data): Office
    {
        $office->update($data);

        return $office;
    }

    public function delete(Office $office): void
    {
        $office->delete();
    }
}
