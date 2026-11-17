<?php

namespace App\Repositories\Contracts;

use App\Models\ExpenseType;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExpenseTypeRepository
{
    /**
     * @return LengthAwarePaginator<int, ExpenseType>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?ExpenseType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ExpenseType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExpenseType $expenseType, array $data): ExpenseType;

    public function delete(ExpenseType $expenseType): void;
}
