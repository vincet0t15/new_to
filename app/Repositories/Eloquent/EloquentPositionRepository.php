<?php

namespace App\Repositories\Eloquent;

use App\Models\Position;
use App\Repositories\Contracts\PositionRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentPositionRepository implements PositionRepository
{
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator
    {
        return Position::query()
            ->with('employmentType:id,name')
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

    public function create(array $data): Position
    {
        return Position::create($data);
    }

    public function update(Position $position, array $data): Position
    {
        $position->update($data);

        return $position;
    }

    public function delete(Position $position): void
    {
        $position->delete();
    }
}
