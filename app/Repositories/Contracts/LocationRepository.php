<?php

namespace App\Repositories\Contracts;

use App\Models\Location;
use Illuminate\Pagination\LengthAwarePaginator;

interface LocationRepository
{
    /**
     * @return LengthAwarePaginator<int, Location>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Location;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Location;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Location $location, array $data): Location;

    public function usageCount(Location $location): int;

    public function delete(Location $location): void;
}
