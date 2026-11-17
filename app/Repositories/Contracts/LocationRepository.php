<?php

namespace App\Repositories\Contracts;

use App\Models\Location;
use Illuminate\Pagination\LengthAwarePaginator;

interface LocationRepository
{
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Location;

    public function create(array $data): Location;

    public function update(Location $location, array $data): Location;

    public function usageCount(Location $location): int;

    public function delete(Location $location): void;
}
