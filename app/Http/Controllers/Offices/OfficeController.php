<?php

namespace App\Http\Controllers\Offices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Offices\StoreOfficeRequest;
use App\Http\Requests\Offices\UpdateOfficeRequest;
use App\Models\Office;
use App\Repositories\Contracts\OfficeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficeController extends Controller
{
    public function __construct(
        private readonly OfficeRepository $offices,
    ) {
        //
    }

    /**
     * Display a listing of the offices.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('offices/index', [
            'offices' => $this->offices->paginate($search !== '' ? $search : null),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created office.
     */
    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        $this->offices->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Office created.')]);

        return back();
    }

    /**
     * Update the specified office.
     */
    public function update(UpdateOfficeRequest $request, Office $office): RedirectResponse
    {
        $this->offices->update($office, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Office updated.')]);

        return back();
    }

    /**
     * Remove the specified office.
     */
    public function destroy(Office $office): RedirectResponse
    {
        // Employees belong to their office, so removing it would leave their
        // records pointing at nothing. They must be reassigned first.
        if ($office->employees()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot delete an office that still has employees.')]);

            return back();
        }

        // A workflow step or designation names the office as its target. Losing
        // it would silently change a chain, so it must be reconfigured first.
        if ($office->workflowSteps()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot delete an office that is used as a workflow step target.')]);

            return back();
        }

        if ($office->designations()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot delete an office that still has designations.')]);

            return back();
        }

        $this->offices->delete($office);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Office deleted.')]);

        return back();
    }
}
