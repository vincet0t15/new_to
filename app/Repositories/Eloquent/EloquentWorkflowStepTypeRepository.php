<?php

namespace App\Repositories\Eloquent;

use App\Models\WorkflowStepType;
use App\Repositories\Contracts\WorkflowStepTypeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentWorkflowStepTypeRepository implements WorkflowStepTypeRepository
{
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator
    {
        return WorkflowStepType::query()
            ->withCount(['steps', 'designations'])
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('signature_title', 'like', '%'.$search.'%');
                });
            })
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allForSelection(): Collection
    {
        return WorkflowStepType::query()->ordered()->get();
    }

    public function create(array $data): WorkflowStepType
    {
        return WorkflowStepType::create($data);
    }

    public function update(WorkflowStepType $stepType, array $data): WorkflowStepType
    {
        $stepType->update($data);

        return $stepType;
    }

    public function delete(WorkflowStepType $stepType): void
    {
        $stepType->delete();
    }
}
