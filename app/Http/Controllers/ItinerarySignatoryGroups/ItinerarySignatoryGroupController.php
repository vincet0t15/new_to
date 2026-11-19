<?php

namespace App\Http\Controllers\ItinerarySignatoryGroups;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItinerarySignatoryGroups\StoreItinerarySignatoryGroupRequest;
use App\Http\Requests\ItinerarySignatoryGroups\UpdateItinerarySignatoryGroupRequest;
use App\Models\Employee;
use App\Models\ItinerarySignatoryGroup;
use App\Models\Office;
use App\Repositories\Contracts\ItinerarySignatoryGroupRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The reusable sets of Itinerary of Travel (Appendix A) signatories.
 *
 * The Appendix A sheet prints two signature lines — CERTIFIED/REVIEWED BY and
 * APPROVED BY — and an office that signs them differently names its own group
 * here, so the set can be reached again from this list rather than re-picked
 * each time.
 */
class ItinerarySignatoryGroupController extends Controller
{
    public function __construct(private readonly ItinerarySignatoryGroupRepository $itinerarySignatoryGroups)
    {
        //
    }

    /**
     * Display a listing of the itinerary signatory groups.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('itinerary-signatory-groups/index', [
            'itinerarySignatoryGroups' => $this->itinerarySignatoryGroups->paginate($request->input('search')),
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
     * Store a newly created itinerary signatory group.
     */
    public function store(StoreItinerarySignatoryGroupRequest $request): RedirectResponse
    {
        $this->itinerarySignatoryGroups->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Itinerary signatory group created.')]);

        return back();
    }

    /**
     * Update the specified itinerary signatory group.
     */
    public function update(UpdateItinerarySignatoryGroupRequest $request, ItinerarySignatoryGroup $itinerarySignatoryGroup): RedirectResponse
    {
        $this->itinerarySignatoryGroups->update($itinerarySignatoryGroup, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Itinerary signatory group updated.')]);

        return back();
    }

    /**
     * Remove the specified itinerary signatory group.
     */
    public function destroy(ItinerarySignatoryGroup $itinerarySignatoryGroup): RedirectResponse
    {
        $this->itinerarySignatoryGroups->delete($itinerarySignatoryGroup);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Itinerary signatory group deleted.')]);

        return back();
    }
}
