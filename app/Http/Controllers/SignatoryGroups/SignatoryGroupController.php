<?php

namespace App\Http\Controllers\SignatoryGroups;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignatoryGroups\StoreSignatoryGroupRequest;
use App\Http\Requests\SignatoryGroups\UpdateSignatoryGroupRequest;
use App\Models\Employee;
use App\Models\Office;
use App\Models\SignatoryGroup;
use App\Repositories\Contracts\SignatoryGroupRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The reusable sets of travel order signatories.
 *
 * A travel order prints three signature lines — CERTIFIED AS TO AVAILABILITY OF
 * APPROPRIATION, APPROVED, and OIC — and a legislative body signs them
 * differently from an executive office. Each group names its own three signers
 * so the set can be reached again from this list rather than re-picked every
 * time.
 */
class SignatoryGroupController extends Controller
{
    public function __construct(private readonly SignatoryGroupRepository $signatoryGroups)
    {
        //
    }

    /**
     * Display a listing of the signatory groups.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('signatory-groups/index', [
            'signatoryGroups' => $this->signatoryGroups->paginate($request->input('search')),
            // Anyone active can be printed as a signer; a login is not needed to
            // hold a pen. The office is shown to tell same-named people apart,
            // which the picker cannot do from the name alone.
            'employees' => Employee::query()
                ->with(['position:id,name', 'office:id,office_name'])
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'position' => $employee->position?->name,
                    'office_name' => $employee->office?->office_name,
                ])
                ->values(),
            // The create and edit dialogs tick the offices that print this
            // group, so the whole list travels with the page.
            'offices' => Office::query()
                ->orderBy('office_name')
                ->get(['id', 'office_name']),
            'filters' => [
                'search' => $request->input('search') ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created signatory group.
     */
    public function store(StoreSignatoryGroupRequest $request): RedirectResponse
    {
        $this->signatoryGroups->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel order signatory group created.')]);

        return back();
    }

    /**
     * Update the specified signatory group.
     */
    public function update(UpdateSignatoryGroupRequest $request, SignatoryGroup $signatoryGroup): RedirectResponse
    {
        $this->signatoryGroups->update($signatoryGroup, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel order signatory group updated.')]);

        return back();
    }

    /**
     * Remove the specified signatory group.
     */
    public function destroy(SignatoryGroup $signatoryGroup): RedirectResponse
    {
        $this->signatoryGroups->delete($signatoryGroup);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel order signatory group deleted.')]);

        return back();
    }
}
