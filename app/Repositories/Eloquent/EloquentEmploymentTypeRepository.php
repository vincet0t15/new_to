<?php

namespace App\Repositories\Eloquent;

use App\Models\EmploymentType;
use App\Repositories\Contracts\EmploymentTypeRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentEmploymentTypeRepository implements EmploymentTypeRepository
{
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator
    {
        return EmploymentType::query()
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->withCount('positions')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): EmploymentType
    {
        return EmploymentType::create($data);
    }

    public function update(EmploymentType $employmentType, array $data): EmploymentType
    {
        $employmentType->update($data);

        return $employmentType;
    }

    public function delete(EmploymentType $employmentType): void
    {
        $employmentType->delete();
    }
}
