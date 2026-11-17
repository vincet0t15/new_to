<?php

namespace App\Http\Controllers\FundSources;

use App\Http\Controllers\Controller;
use App\Http\Requests\FundSources\StoreFundSourceRequest;
use App\Http\Requests\FundSources\UpdateFundSourceRequest;
use App\Models\FundSource;
use App\Repositories\Contracts\FundSourceRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FundSourceController extends Controller
{
    public function __construct(private readonly FundSourceRepository $fundSources) {}

    public function index(Request $request): Response
    {
        return Inertia::render('fund-sources/index', [
            'fundSources' => $this->fundSources->paginate($request->input('search')),
            'filters' => ['search' => $request->input('search') ?? ''],
        ]);
    }

    public function store(StoreFundSourceRequest $request): RedirectResponse
    {
        $this->fundSources->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fund source created.')]);

        return redirect()->route('fund-sources.index');
    }

    public function update(UpdateFundSourceRequest $request, FundSource $fundSource): RedirectResponse
    {
        $this->fundSources->update($fundSource, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fund source updated.')]);

        return redirect()->route('fund-sources.index');
    }

    public function destroy(FundSource $fundSource): RedirectResponse
    {
        if ($this->fundSources->usageCount($fundSource) > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This fund source is already used by a travel order and cannot be deleted. Set it to inactive instead.'),
            ]);

            return back();
        }

        $this->fundSources->delete($fundSource);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fund source deleted.')]);

        return redirect()->route('fund-sources.index');
    }
}
