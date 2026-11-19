<?php

namespace App\Repositories\Contracts;

use App\Models\SignatoryGroup;
use Illuminate\Pagination\LengthAwarePaginator;

interface SignatoryGroupRepository
{
    /**
     * @return LengthAwarePaginator<int, SignatoryGroup>
     */
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SignatoryGroup;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SignatoryGroup $signatoryGroup, array $data): SignatoryGroup;

    public function delete(SignatoryGroup $signatoryGroup): void;
}
