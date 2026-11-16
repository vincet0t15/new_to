<?php

namespace App\Repositories\Contracts;

use App\Models\EmploymentType;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmploymentTypeRepository
{
    /**
     * @return LengthAwarePaginator<int, EmploymentType>
     */
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EmploymentType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmploymentType $employmentType, array $data): EmploymentType;

    public function delete(EmploymentType $employmentType): void;
}
