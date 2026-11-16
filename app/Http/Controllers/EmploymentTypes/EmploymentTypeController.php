<?php

namespace App\Http\Controllers\EmploymentTypes;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmploymentTypes\StoreEmploymentTypeRequest;
use App\Http\Requests\EmploymentTypes\UpdateEmploymentTypeRequest;
use App\Models\EmploymentType;
use App\Repositories\Contracts\EmploymentTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmploymentTypeController extends Controller
{
    public function __construct(private readonly EmploymentTypeRepository $employmentTypes)
    {
        //
    }

    /**
     * Display a listing of the employment types.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('employment-types/index', [
            'employmentTypes' => $this->employmentTypes->paginate($search !== '' ? $search : null),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created employment type.
     */
    public function store(StoreEmploymentTypeRequest $request): RedirectResponse
    {
        $this->employmentTypes->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employment type created.')]);

        return back();
    }

    /**
     * Update the specified employment type.
     */
    public function update(UpdateEmploymentTypeRequest $request, EmploymentType $employmentType): RedirectResponse
    {
        $this->employmentTypes->update($employmentType, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employment type updated.')]);

        return back();
    }

    /**
     * Remove the specified employment type.
     */
    public function destroy(EmploymentType $employmentType): RedirectResponse
    {
        // Positions point at their employment type, and employees point at one
        // directly. Removing either dependency's type would leave that record
        // without the classification it was filed under.
        if ($employmentType->positions()->exists() || $employmentType->employees()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot delete an employment type that still has positions or employees.')]);

            return back();
        }

        $this->employmentTypes->delete($employmentType);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employment type deleted.')]);

        return back();
    }
}
