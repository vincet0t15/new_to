<?php

namespace App\Repositories\Eloquent;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Repositories\Contracts\WorkflowRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentWorkflowRepository implements WorkflowRepository
{
    public function paginate(?string $search, int $perPage = 50): LengthAwarePaginator
    {
        return Workflow::query()
            ->withCount(['steps', 'offices'])
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allForSelection(): Collection
    {
        return Workflow::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function activeForSelection(): Collection
    {
        return Workflow::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Workflow
    {
        return DB::transaction(function () use ($data): Workflow {
            $isDefault = (bool) ($data['is_default'] ?? false);

            if ($isDefault) {
                $this->clearOtherDefaults();
                $data['is_active'] = true;
            }

            return Workflow::create($data);
        });
    }

    public function update(Workflow $workflow, array $data): Workflow
    {
        return DB::transaction(function () use ($workflow, $data): Workflow {
            $isDefault = (bool) ($data['is_default'] ?? $workflow->is_default);

            if ($isDefault) {
                $this->clearOtherDefaults($workflow);
                $data['is_active'] = true;
            }

            $workflow->update($data);

            return $workflow;
        });
    }

    public function delete(Workflow $workflow): void
    {
        $workflow->delete();
    }

    public function duplicate(Workflow $workflow, string $name, string $slug): Workflow
    {
        return DB::transaction(function () use ($workflow, $name, $slug): Workflow {
            $copy = Workflow::create([
                'name' => $name,
                'slug' => $slug,
                'description' => $workflow->description,
                'is_default' => false,
                'is_active' => $workflow->is_active,
            ]);

            foreach ($workflow->steps()->get() as $step) {
                $copy->steps()->create([
                    'workflow_step_type_id' => $step->workflow_step_type_id,
                    'label' => $step->label,
                    'office_id' => $step->office_id,
                    'sort_order' => $step->sort_order,
                    'auto_skip_creator' => $step->auto_skip_creator,
                    'is_active' => $step->is_active,
                ]);
            }

            return $copy;
        });
    }

    public function saveStep(Workflow $workflow, ?WorkflowStep $step, array $data): WorkflowStep
    {
        if ($step === null) {
            return $workflow->steps()->create($data);
        }

        $step->update($data);

        return $step;
    }

    public function deleteStep(WorkflowStep $step): void
    {
        $step->delete();
    }

    public function moveStep(WorkflowStep $step, string $direction): void
    {
        $steps = WorkflowStep::query()
            ->where('workflow_id', $step->workflow_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values()
            ->all();

        $index = null;

        foreach ($steps as $position => $candidate) {
            if ($candidate->id === $step->id) {
                $index = $position;

                break;
            }
        }

        if ($index === null) {
            return;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if (! array_key_exists($target, $steps)) {
            return;
        }

        [$steps[$index], $steps[$target]] = [$steps[$target], $steps[$index]];

        DB::transaction(function () use ($steps): void {
            foreach ($steps as $position => $ordered) {
                $ordered->update(['sort_order' => $position + 1]);
            }
        });
    }

    /**
     * Leave exactly one workflow marked as the fallback.
     */
    private function clearOtherDefaults(?Workflow $except = null): void
    {
        $query = Workflow::query()->where('is_default', true);

        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }

        $query->update(['is_default' => false]);
    }
}
