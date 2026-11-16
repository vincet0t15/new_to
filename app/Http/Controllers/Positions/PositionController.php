<?php

namespace App\Http\Controllers\Positions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Positions\StorePositionRequest;
use App\Http\Requests\Positions\UpdatePositionRequest;
use App\Models\EmploymentType;
use App\Models\Position;
use App\Repositories\Contracts\PositionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function __construct(private readonly PositionRepository $positions)
    {
        //
    }

    /**
     * Display a listing of the positions.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('positions/index', [
            'positions' => $this->positions->paginate($search !== '' ? $search : null),
            'employmentTypes' => EmploymentType::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created position.
     */
    public function store(StorePositionRequest $request): RedirectResponse
    {
        $this->positions->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Position created.')]);

        return back();
    }

    /**
     * Update the specified position.
     */
    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $this->positions->update($position, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Position updated.')]);

        return back();
    }

    /**
     * Remove the specified position.
     */
    public function destroy(Position $position): RedirectResponse
    {
        if ($position->employees()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot delete a position that is still held by an employee.')]);

            return back();
        }

        $this->positions->delete($position);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Position deleted.')]);

        return back();
    }
}
