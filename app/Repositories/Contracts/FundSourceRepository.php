<?php

namespace App\Repositories\Contracts;

use App\Models\FundSource;
use Illuminate\Pagination\LengthAwarePaginator;

interface FundSourceRepository
{
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?FundSource;

    public function create(array $data): FundSource;

    public function update(FundSource $fundSource, array $data): FundSource;

    public function usageCount(FundSource $fundSource): int;

    public function delete(FundSource $fundSource): void;
}
