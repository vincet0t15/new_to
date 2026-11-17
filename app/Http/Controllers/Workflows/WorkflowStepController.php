<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflows\StoreWorkflowStepRequest;
use App\Http\Requests\Workflows\UpdateWorkflowStepRequest;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Repositories\Contracts\WorkflowRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkflowStepController extends Controller
{
    public function __construct(private readonly WorkflowRepository $workflows)
    {
        //
    }

    /**
     * Add a step to a workflow.
     */
    public function store(StoreWorkflowStepRequest $request, Workflow $workflow): RedirectResponse
    {
        $data = $request->validated();
        $data['reviewed_fields'] ??= [];

        $this->workflows->saveStep($workflow, null, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Step added to the workflow.')]);

        return back();
    }

    /**
     * Update an existing step.
     */
    public function update(
        UpdateWorkflowStepRequest $request,
        Workflow $workflow,
        WorkflowStep $step,
    ): RedirectResponse {
        $this->ensureStepBelongsToWorkflow($workflow, $step);

        $this->workflows->saveStep($workflow, $step, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Step updated.')]);

        return back();
    }

    /**
     * Move a step one position up or down.
     */
    public function move(Request $request, Workflow $workflow, WorkflowStep $step): RedirectResponse
    {
        $this->ensureStepBelongsToWorkflow($workflow, $step);

        $validated = $request->validate([
            'direction' => ['required', 'string', 'in:up,down'],
        ]);

        $this->workflows->moveStep($step, $validated['direction']);

        return back();
    }

    /**
     * Remove a step from a workflow.
     *
     * The routed_steps snapshot on an in-flight travel order stores step ids,
     * so a step may not disappear while an order still relies on the chain.
     */
    public function destroy(Request $request, Workflow $workflow, WorkflowStep $step): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->isSuperAdministrator(),
            403,
            'Only a Super Admin may delete workflow configuration.',
        );

        $this->ensureStepBelongsToWorkflow($workflow, $step);

        $inFlight = DB::table('travel_orders')
            ->where('workflow_id', $workflow->id)
            ->whereNotIn('status', ['draft', 'cancelled', 'completed', 'rejected'])
            ->exists();

        if ($inFlight) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Steps cannot be removed while travel orders are still following this workflow.')]);

            return back();
        }

        $this->workflows->deleteStep($step);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Step removed from the workflow.')]);

        return back();
    }

    /**
     * A nested step must belong to the workflow in the URL.
     */
    private function ensureStepBelongsToWorkflow(Workflow $workflow, WorkflowStep $step): void
    {
        abort_unless($step->workflow_id === $workflow->id, 404);
    }
}
