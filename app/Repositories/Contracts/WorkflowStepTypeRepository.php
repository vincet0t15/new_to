<?php

namespace App\Repositories\Contracts;

use App\Models\WorkflowStepType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkflowStepTypeRepository
{
    /**
     * @return LengthAwarePaginator<int, WorkflowStepType>
     */
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator;

    /**
     * @return Collection<int, WorkflowStepType>
     */
    public function allForSelection(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkflowStepType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkflowStepType $stepType, array $data): WorkflowStepType;

    public function delete(WorkflowStepType $stepType): void;
}
