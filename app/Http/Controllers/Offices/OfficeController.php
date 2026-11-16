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
    public function __construct(private readonly OfficeRepository $offices)
    {
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
        // Employees and workflow designations will restrict deletion once those
        // modules exist. Until then an office has no dependants to check.
        $this->offices->delete($office);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Office deleted.')]);

        return back();
    }
}
