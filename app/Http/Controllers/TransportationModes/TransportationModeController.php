<?php

namespace App\Http\Controllers\TransportationModes;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransportationModes\StoreTransportationModeRequest;
use App\Http\Requests\TransportationModes\UpdateTransportationModeRequest;
use App\Models\TransportationMode;
use App\Repositories\Contracts\TransportationModeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('transportation-modes/index', [
            'transportationModes' => $modes,
            'filters' => [
                'search' => $request->input('search') ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('transportation-modes/create');
    }

    public function store(StoreTransportationModeRequest $request): RedirectResponse
    {
        $this->transportationModes->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transportation mode created.')]);

        return redirect()->route('transportation-modes.index');
    }

    public function edit(TransportationMode $transportationMode): Response
    {
        return Inertia::render('transportation-modes/edit', [
            'transportationMode' => $transportationMode,
        ]);
    }

    public function update(UpdateTransportationModeRequest $request, TransportationMode $transportationMode): RedirectResponse
    {
        $this->transportationModes->update($transportationMode, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transportation mode updated.')]);

        return redirect()->route('transportation-modes.index');
    }

    public function delete(TransportationMode $transportationMode): Response
    {
        return Inertia::render('transportation-modes/delete', [
            'transportationMode' => $transportationMode,
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
}
