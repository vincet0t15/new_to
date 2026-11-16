<?php

namespace App\Repositories\Contracts;

use App\Models\Office;
use Illuminate\Pagination\LengthAwarePaginator;

interface OfficeRepository
{
    /**
     * @return LengthAwarePaginator<int, Office>
     */
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Office;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Office $office, array $data): Office;

    public function delete(Office $office): void;
}
