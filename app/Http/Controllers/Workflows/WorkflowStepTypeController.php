<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkflowStepTypes\StoreWorkflowStepTypeRequest;
use App\Http\Requests\WorkflowStepTypes\UpdateWorkflowStepTypeRequest;
use App\Models\User;
use App\Models\WorkflowStepType;
use App\Repositories\Contracts\WorkflowStepTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowStepTypeController extends Controller
{
    public function __construct(private readonly WorkflowStepTypeRepository $stepTypes)
    {
        //
    }

    /**
     * List the reusable step definitions.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('workflow-step-types/index', [
            'stepTypes' => $this->stepTypes->paginate($search !== '' ? $search : null),
            'filters' => [
                'search' => $search,
            ],
            'canDelete' => $this->canDelete($request),
        ]);
    }

    /**
     * Create a reusable step definition.
     */
    public function store(StoreWorkflowStepTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->stepTypes->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Step type created.')]);

        return back();
    }

    /**
     * Update a reusable step definition.
     */
    public function update(
        UpdateWorkflowStepTypeRequest $request,
        WorkflowStepType $stepType,
    ): RedirectResponse {
        $this->stepTypes->update($stepType, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Step type updated.')]);

        return back();
    }

    /**
     * Remove a step type that no workflow or office still references.
     */
    public function destroy(Request $request, WorkflowStepType $stepType): RedirectResponse
    {
        abort_unless($this->canDelete($request), 403, 'Only a Super Admin may delete workflow configuration.');

        if ($stepType->steps()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This step type is used by a workflow and cannot be deleted.')]);

            return back();
        }

        if ($stepType->designations()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This step type still has office designations. End them first.')]);

            return back();
        }

        $this->stepTypes->delete($stepType);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Step type deleted.')]);

        return back();
    }

    /**
     * Only a Super Admin may delete workflow configuration.
     */
    private function canDelete(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->isSuperAdministrator();
    }
}
