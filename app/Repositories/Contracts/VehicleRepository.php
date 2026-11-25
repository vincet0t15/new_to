<?php

namespace App\Repositories\Contracts;

use App\Models\Vehicle;
use Illuminate\Pagination\LengthAwarePaginator;

interface VehicleRepository
{
    /**
     * @return LengthAwarePaginator<int, Vehicle>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Vehicle;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vehicle;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Vehicle $vehicle, array $data): Vehicle;

    public function delete(Vehicle $vehicle): void;
}
