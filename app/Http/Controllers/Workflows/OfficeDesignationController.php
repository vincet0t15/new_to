<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfficeDesignations\StoreOfficeDesignationRequest;
use App\Http\Requests\OfficeDesignations\UpdateOfficeDesignationRequest;
use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeDesignation;
use App\Models\WorkflowStepType;
use App\Repositories\Contracts\OfficeDesignationRepository;
use App\Repositories\Contracts\WorkflowStepTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficeDesignationController extends Controller
{
    public function __construct(
        private readonly OfficeDesignationRepository $designations,
        private readonly WorkflowStepTypeRepository $stepTypes,
    ) {
        //
    }

    /**
     * List who answers each step for each office.
     */
    public function index(Request $request): Response
    {
        $officeId = $request->filled('office_id') ? $request->integer('office_id') : null;
        $stepTypeId = $request->filled('workflow_step_type_id')
            ? $request->integer('workflow_step_type_id')
            : null;

        return Inertia::render('designations/index', [
            'designations' => $this->designations->paginate($officeId, $stepTypeId),
            'filters' => [
                'office_id' => $officeId,
                'workflow_step_type_id' => $stepTypeId,
            ],
            'offices' => Office::query()
                ->orderBy('office_name')
                ->get(['id', 'office_name']),
            'stepTypes' => $this->stepTypes->allForSelection(),
            'employees' => $this->employeeOptions(),
        ]);
    }

    /**
     * Designate somebody as a primary holder or deputy for a step.
     */
    public function store(StoreOfficeDesignationRequest $request): RedirectResponse
    {
        $this->designations->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Designation added.')]);

        return back();
    }

    /**
     * Change the holder, deputy flag, effectivity, or active state.
     */
    public function update(
        UpdateOfficeDesignationRequest $request,
        OfficeDesignation $designation,
    ): RedirectResponse {
        $this->designations->update($designation, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Designation updated.')]);

        return back();
    }

    /**
     * End a designation without erasing who held it in the past.
     */
    public function destroy(OfficeDesignation $designation): RedirectResponse
    {
        $endsAt = $designation->starts_at !== null && $designation->starts_at->isFuture()
            ? $designation->starts_at->toDateString()
            : now()->toDateString();

        $this->designations->update($designation, [
            'is_active' => false,
            'ends_at' => $endsAt,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Designation ended.')]);

        return back();
    }

    /**
     * Active employees with the office and account state the form needs.
     *
     * @return array<int, array{id: int, full_name: string, office_id: int, office_name: string, has_account: bool}>
     */
    private function employeeOptions(): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->with(['office:id,office_name', 'user:id,employee_id'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'office_id' => $employee->office_id,
                'office_name' => $employee->office->office_name,
                'has_account' => $employee->user !== null,
            ])
            ->values()
            ->all();
    }
}
