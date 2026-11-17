<?php

namespace App\Http\Controllers\TravelClassifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\TravelClassifications\StoreTravelClassificationRequest;
use App\Http\Requests\TravelClassifications\UpdateTravelClassificationRequest;
use App\Models\TravelClassification;
use App\Repositories\Contracts\TravelClassificationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TravelClassificationController extends Controller
{
    public function __construct(private readonly TravelClassificationRepository $classifications)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('travel-classifications/index', [
            'travelClassifications' => $this->classifications->paginate($request->input('search')),
            'filters' => ['search' => $request->input('search') ?? ''],
        ]);
    }

    public function store(StoreTravelClassificationRequest $request): RedirectResponse
    {
        $this->classifications->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel classification created.')]);

        return redirect()->route('travel-classifications.index');
    }

    public function update(
        UpdateTravelClassificationRequest $request,
        TravelClassification $travelClassification,
    ): RedirectResponse {
        $this->classifications->update($travelClassification, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel classification updated.')]);

        return redirect()->route('travel-classifications.index');
    }

    public function destroy(TravelClassification $travelClassification): RedirectResponse
    {
        if ($this->classifications->usageCount($travelClassification) > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This travel classification is already used by a travel order and cannot be deleted. Set it to inactive instead.'),
            ]);

            return back();
        }

        $this->classifications->delete($travelClassification);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel classification deleted.')]);

        return redirect()->route('travel-classifications.index');
    }
}
