<?php

namespace App\Http\Controllers\FuelTypes;

use App\Http\Controllers\Controller;
use App\Http\Requests\FuelTypes\StoreFuelTypeRequest;
use App\Http\Requests\FuelTypes\UpdateFuelTypeRequest;
use App\Models\FuelType;
use App\Repositories\Contracts\FuelTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FuelTypeController extends Controller
{
    public function __construct(private readonly FuelTypeRepository $fuelTypes) {}

    public function index(Request $request): Response
    {
        return Inertia::render('fuel-types/index', [
            'fuelTypes' => $this->fuelTypes->paginate($request->input('search')),
            'filters' => ['search' => $request->input('search') ?? ''],
        ]);
    }

    public function store(StoreFuelTypeRequest $request): RedirectResponse
    {
        $this->fuelTypes->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fuel type created.')]);

        return back();
    }

    public function update(UpdateFuelTypeRequest $request, FuelType $fuelType): RedirectResponse
    {
        $this->fuelTypes->update($fuelType, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fuel type updated.')]);

        return back();
    }

    public function destroy(FuelType $fuelType): RedirectResponse
    {
        $usageCount = $fuelType->tripTickets()->count() + $fuelType->fuelAllocations()->count();
        if ($usageCount > 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot delete: fuel type is in use.')]);

            return back();
        }

        $this->fuelTypes->delete($fuelType);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fuel type deleted.')]);

        return back();
    }
}
