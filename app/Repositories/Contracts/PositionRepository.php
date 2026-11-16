<?php

namespace App\Repositories\Contracts;

use App\Models\Position;
use Illuminate\Pagination\LengthAwarePaginator;

interface PositionRepository
{
    /**
     * @return LengthAwarePaginator<int, Position>
     */
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Position;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Position $position, array $data): Position;

    public function delete(Position $position): void;
}
