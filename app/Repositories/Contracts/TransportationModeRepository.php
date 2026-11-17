<?php

namespace App\Repositories\Contracts;

use App\Models\TransportationMode;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransportationModeRepository
{
    /**
     * @return LengthAwarePaginator<int, TransportationMode>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?TransportationMode;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TransportationMode;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TransportationMode $mode, array $data): TransportationMode;

    public function delete(TransportationMode $mode): void;

    public function usageCount(TransportationMode $mode): int;
}
