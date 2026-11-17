<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflows\StoreWorkflowRequest;
use App\Http\Requests\Workflows\UpdateWorkflowRequest;
use App\Models\Office;
use App\Models\User;
use App\Models\Workflow;
use App\Repositories\Contracts\WorkflowRepository;
use App\Repositories\Contracts\WorkflowStepTypeRepository;
use App\Services\WorkflowRouteResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowRepository $workflows,
        private readonly WorkflowStepTypeRepository $stepTypes,
        private readonly WorkflowRouteResolver $routes,
    ) {
        //
    }

    /**
     * List the configured workflows.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('workflows/index', [
            'workflows' => $this->workflows->paginate($search !== '' ? $search : null),
            'filters' => [
                'search' => $search,
            ],
            'canDelete' => $this->canDelete($request),
        ]);
    }

    /**
     * Store a new workflow and open its step designer.
     */
    public function store(StoreWorkflowRequest $request): RedirectResponse
    {
        $workflow = $this->workflows->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workflow created. Add its steps below.')]);

        return to_route('workflows.edit', $workflow);
    }

    /**
     * Show the workflow's details and ordered steps.
     */
    public function edit(Request $request, Workflow $workflow): Response
    {
        $workflow->load(['steps.stepType', 'steps.office']);

        return Inertia::render('workflows/edit', [
            'workflow' => $workflow,
            'stepTypes' => $this->stepTypes->allForSelection(),
            'offices' => Office::query()
                ->with('workflow:id,name,is_default,is_active')
                ->orderBy('office_name')
                ->get(['id', 'office_name', 'workflow_id']),
            'assignedOfficeIds' => $workflow->offices()->pluck('id')->all(),
            'reviewableFields' => config('travel-orders.resubmission.reviewable_fields', []),
            'canDelete' => $this->canDelete($request),
        ]);
    }

    /**
     * Update the workflow's own fields.
     */
    public function update(UpdateWorkflowRequest $request, Workflow $workflow): RedirectResponse
    {
        $this->workflows->update($workflow, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workflow updated.')]);

        return back();
    }

    /**
     * Copy a workflow and its steps so a variant can start from an existing
     * chain instead of being rebuilt by hand.
     */
    public function duplicate(Request $request, Workflow $workflow): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $requestedName = $request->string('name')->toString();
        $name = $this->uniqueName($requestedName !== '' ? $requestedName : $workflow->name.' Copy');
        $copy = $this->workflows->duplicate($workflow, $name, $this->uniqueSlug(Str::slug($name)));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workflow duplicated. Review its steps before activating it.')]);

        return to_route('workflows.edit', $copy);
    }

    /**
     * Remove a workflow that is not the fallback and is not in use.
     */
    public function destroy(Request $request, Workflow $workflow): RedirectResponse
    {
        abort_unless($this->canDelete($request), 403, 'Only a Super Admin may delete workflow configuration.');

        if ($workflow->is_default) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('The default workflow cannot be deleted. Set another workflow as the default first.')]);

            return back();
        }

        if ($workflow->offices()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This workflow is still assigned to an office. Reassign those offices first.')]);

            return back();
        }

        if (DB::table('travel_orders')->where('workflow_id', $workflow->id)->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This workflow is recorded on travel orders and cannot be deleted.')]);

            return back();
        }

        $this->workflows->delete($workflow);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workflow deleted.')]);

        return to_route('workflows.index');
    }

    /**
     * Preview the chain an office resolves to, with its current designees.
     */
    public function preview(Request $request): Response
    {
        $selectedId = $request->filled('office_id') ? $request->integer('office_id') : null;
        $office = $selectedId === null ? null : Office::query()->find($selectedId);

        return Inertia::render('workflows/preview', [
            'offices' => Office::query()
                ->orderBy('office_name')
                ->get(['id', 'office_name', 'workflow_id']),
            'selectedOfficeId' => $office?->id,
            'preview' => $office === null ? null : $this->routes->preview($office),
        ]);
    }

    /**
     * Only a Super Admin may delete workflow configuration.
     */
    private function canDelete(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->isSuperAdministrator();
    }

    /**
     * Make a copy's name unique without asking the administrator to think of
     * one; a duplicate is usually a starting point, not a final name.
     */
    private function uniqueName(string $base): string
    {
        $name = $base;
        $suffix = 2;

        while (Workflow::query()->where('name', $name)->exists()) {
            $name = $base.' ('.$suffix.')';
            $suffix++;
        }

        return $name;
    }

    /**
     * @see uniqueName()
     */
    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while (Workflow::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
