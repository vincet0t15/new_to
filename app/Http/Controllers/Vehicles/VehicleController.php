<?php

namespace App\Http\Controllers\Vehicles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicles\StoreVehicleRequest;
use App\Http\Requests\Vehicles\UpdateVehicleRequest;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Repositories\Contracts\VehicleRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function __construct(private readonly VehicleRepository $vehicles) {}

    public function index(Request $request): Response
    {
        return Inertia::render('vehicles/index', [
            'vehicles' => $this->vehicles->paginate($request->input('search')),
            'drivers' => $this->driverOptions(),
            'filters' => ['search' => $request->input('search') ?? ''],
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $this->vehicles->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle registered.')]);

        return back();
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->vehicles->update($vehicle, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle updated.')]);

        return back();
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->tripTickets()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Cannot delete: this vehicle is used by a trip ticket. Set it to inactive instead.'),
            ]);

            return back();
        }

        $this->vehicles->delete($vehicle);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle deleted.')]);

        return back();
    }

    /**
     * @return array<int, array{id: int, full_name: string}>
     */
    private function driverOptions(): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
            ])
            ->values()
            ->all();
    }
}
