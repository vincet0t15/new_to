<?php

namespace App\Repositories\Contracts;

use App\Models\OfficeDesignation;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Collection;
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
     * Everyone who answers the given workflow step.
     *
     * A step with no target office follows the filing office, so its step type
     * is matched across offices instead of one fixed office.
     *
     * @return Collection<int, OfficeDesignation>
     */
    public function forStep(WorkflowStep $step): Collection;

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
