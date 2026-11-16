<?php

namespace App\Repositories\Contracts;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkflowRepository
{
    /**
     * @return LengthAwarePaginator<int, Workflow>
     */
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator;

    /**
     * Every workflow, for selectors that must also show an inactive assignment.
     *
     * @return Collection<int, Workflow>
     */
    public function allForSelection(): Collection;

    /**
     * @return Collection<int, Workflow>
     */
    public function activeForSelection(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Workflow;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Workflow $workflow, array $data): Workflow;

    public function delete(Workflow $workflow): void;

    public function duplicate(Workflow $workflow, string $name, string $slug): Workflow;

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveStep(Workflow $workflow, ?WorkflowStep $step, array $data): WorkflowStep;

    public function deleteStep(WorkflowStep $step): void;

    public function moveStep(WorkflowStep $step, string $direction): void;
}
