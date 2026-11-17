<?php

namespace App\Http\Controllers\Locations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Locations\StoreLocationRequest;
use App\Http\Requests\Locations\UpdateLocationRequest;
use App\Models\Location;
use App\Repositories\Contracts\LocationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function __construct(private readonly LocationRepository $locations)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('locations/index', [
            'locations' => $this->locations->paginate($request->input('search')),
            'filters' => ['search' => $request->input('search') ?? ''],
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $this->locations->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Location created.')]);

        return redirect()->route('locations.index');
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $this->locations->update($location, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Location updated.')]);

        return redirect()->route('locations.index');
    }

    public function destroy(Location $location): RedirectResponse
    {
        if ($this->locations->usageCount($location) > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This location is already used by a fare or per diem configuration and cannot be deleted. Set it to inactive instead.'),
            ]);

            return back();
        }

        $this->locations->delete($location);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Location deleted.')]);

        return redirect()->route('locations.index');
    }
}
