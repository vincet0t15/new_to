<?php

namespace App\Repositories\Contracts;

use App\Models\OfficeDesignation;
use Illuminate\Pagination\LengthAwarePaginator;

interface OfficeDesignationRepository
{
    /**
     * @return LengthAwarePaginator<int, OfficeDesignation>
     */
    public function paginate(
        ?int $officeId,
        ?int $stepTypeId,
        int $perPage = 50,
    ): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OfficeDesignation;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OfficeDesignation $designation, array $data): OfficeDesignation;

    public function delete(OfficeDesignation $designation): void;
}
