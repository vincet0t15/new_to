<?php

namespace App\Repositories\Contracts;

use App\Models\ExpenseType;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExpenseTypeRepository
{
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?ExpenseType;

    public function create(array $data): ExpenseType;

    public function update(ExpenseType $expenseType, array $data): ExpenseType;

    public function delete(ExpenseType $expenseType): void;
}
