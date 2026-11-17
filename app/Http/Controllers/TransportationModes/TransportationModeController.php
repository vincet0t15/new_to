<?php

namespace App\Http\Controllers\TransportationModes;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransportationModes\StoreTransportationModeRequest;
use App\Http\Requests\TransportationModes\UpdateTransportationModeRequest;
use App\Models\Location;
use App\Models\TransportationMode;
use App\Repositories\Contracts\TransportationModeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransportationModeController extends Controller
{
    public function __construct(
        private readonly TransportationModeRepository $transportationModes,
    ) {
        //
    }

    public function index(Request $request): Response
    {
        $modes = $this->transportationModes->paginate($request->input('search'));
        $modes->getCollection()->each(function (TransportationMode $mode): void {
            $mode->load('fares.location:id,name');
            $mode->setAttribute('usage_count', $this->transportationModes->usageCount($mode));
        });

        return Inertia::render('transportation-modes/index', [
            'transportationModes' => $modes,
            'locations' => $this->selectableLocations(),
            'filters' => [
                'search' => $request->input('search') ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('transportation-modes/create', [
            'locations' => $this->selectableLocations(),
        ]);
    }

    public function store(StoreTransportationModeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $mode = $this->transportationModes->create(Arr::except($data, ['fares']));
            $this->syncFares($mode, $data['fares'] ?? []);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transportation mode created.')]);

        return redirect()->route('transportation-modes.index');
    }

    public function edit(TransportationMode $transportationMode): Response
    {
        return Inertia::render('transportation-modes/edit', [
            'transportationMode' => $transportationMode
                ->load('fares.location:id,name')
                ->setAttribute('usage_count', $this->transportationModes->usageCount($transportationMode)),
            'locations' => $this->selectableLocations(),
        ]);
    }

    public function update(UpdateTransportationModeRequest $request, TransportationMode $transportationMode): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($transportationMode, $data): void {
            $this->transportationModes->update($transportationMode, Arr::except($data, ['fares']));
            $this->syncFares($transportationMode, $data['fares'] ?? []);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transportation mode updated.')]);

        return redirect()->route('transportation-modes.index');
    }

    public function delete(TransportationMode $transportationMode): Response
    {
        return Inertia::render('transportation-modes/delete', [
            'transportationMode' => $transportationMode
                ->load('fares.location:id,name')
                ->setAttribute('usage_count', $this->transportationModes->usageCount($transportationMode)),
            'usageCount' => $this->transportationModes->usageCount($transportationMode),
        ]);
    }

    public function destroy(TransportationMode $transportationMode): RedirectResponse
    {
        if ($this->transportationModes->usageCount($transportationMode) > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This transportation mode is already used by a travel order and cannot be deleted. Set it to inactive instead.'),
            ]);

            return back();
        }

        $this->transportationModes->delete($transportationMode);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transportation mode deleted.')]);

        return redirect()->route('transportation-modes.index');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Location>
     */
    private function selectableLocations(): \Illuminate\Database\Eloquent\Collection
    {
        return Location::query()->active()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @param  array<int, array{location_id: int|string, amount: int|float|string}>  $fares
     */
    private function syncFares(TransportationMode $mode, array $fares): void
    {
        $mode->fares()->delete();

        foreach ($fares as $fare) {
            $mode->fares()->create([
                'location_id' => $fare['location_id'],
                'amount' => $fare['amount'],
            ]);
        }
    }
}
