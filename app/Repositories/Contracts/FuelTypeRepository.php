<?php

namespace App\Repositories\Contracts;

use App\Models\FuelType;
use Illuminate\Pagination\LengthAwarePaginator;

interface FuelTypeRepository
{
    /**
     * @return LengthAwarePaginator<int, FuelType>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?FuelType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FuelType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FuelType $fuelType, array $data): FuelType;

    public function delete(FuelType $fuelType): void;
}
