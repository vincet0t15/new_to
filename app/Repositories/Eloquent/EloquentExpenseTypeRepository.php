<?php

namespace App\Repositories\Eloquent;

use App\Models\ExpenseType;
use App\Repositories\Contracts\ExpenseTypeRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentExpenseTypeRepository implements ExpenseTypeRepository
{
    /**
     * @return LengthAwarePaginator<int, ExpenseType>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = ExpenseType::query()
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->each(fn (ExpenseType $expenseType) => $expenseType->setAttribute('usage_count', 0));

        return $paginator;
    }

    public function find(int $id): ?ExpenseType
    {
        return ExpenseType::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ExpenseType
    {
        return ExpenseType::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExpenseType $expenseType, array $data): ExpenseType
    {
        $expenseType->update($data);

        return $expenseType;
    }

    public function delete(ExpenseType $expenseType): void
    {
        $expenseType->delete();
    }
}
