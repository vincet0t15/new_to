<?php

namespace App\Repositories\Contracts;

use App\Models\FundSource;
use Illuminate\Pagination\LengthAwarePaginator;

interface FundSourceRepository
{
    /**
     * @return LengthAwarePaginator<int, FundSource>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?FundSource;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FundSource;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FundSource $fundSource, array $data): FundSource;

    public function usageCount(FundSource $fundSource): int;

    public function delete(FundSource $fundSource): void;
}
