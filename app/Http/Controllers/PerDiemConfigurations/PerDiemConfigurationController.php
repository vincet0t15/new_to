<?php

namespace App\Http\Controllers\PerDiemConfigurations;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerDiemConfigurations\StorePerDiemConfigurationRequest;
use App\Http\Requests\PerDiemConfigurations\StorePerDiemMealRuleRequest;
use App\Http\Requests\PerDiemConfigurations\UpdatePerDiemConfigurationRequest;
use App\Http\Requests\PerDiemConfigurations\UpdatePerDiemMealRuleRequest;
use App\Models\Location;
use App\Models\MealType;
use App\Models\PerDiemConfiguration;
use App\Models\PerDiemMealRule;
use App\Repositories\Contracts\PerDiemConfigurationRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PerDiemConfigurationController extends Controller
{
    public function __construct(private readonly PerDiemConfigurationRepository $configurations) {}

    public function index(Request $request): Response
    {
        return Inertia::render('per-diem-configurations/index', [
            'perDiemConfigurations' => $this->configurations->paginate($request->input('search')),
            'filters' => ['search' => $request->input('search') ?? ''],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('per-diem-configurations/create', [
            'locations' => $this->selectableLocations(),
        ]);
    }

    public function store(StorePerDiemConfigurationRequest $request): RedirectResponse
    {
        $this->configurations->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Per diem configuration created.')]);

        return redirect()->route('per-diem-configurations.index');
    }

    public function edit(PerDiemConfiguration $perDiemConfiguration): Response
    {
        return Inertia::render('per-diem-configurations/edit', [
            'perDiemConfiguration' => $perDiemConfiguration->load('locations:id,name'),
            'locations' => $this->selectableLocations(),
        ]);
    }

    public function update(
        UpdatePerDiemConfigurationRequest $request,
        PerDiemConfiguration $perDiemConfiguration,
    ): RedirectResponse {
        $this->configurations->update($perDiemConfiguration, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Per diem configuration updated.')]);

        return redirect()->route('per-diem-configurations.index');
    }

    public function delete(PerDiemConfiguration $perDiemConfiguration): Response
    {
        return Inertia::render('per-diem-configurations/delete', [
            'perDiemConfiguration' => $perDiemConfiguration,
            'mealRules' => $this->configurations->mealRules($perDiemConfiguration),
            'usageCount' => 0,
        ]);
    }

    public function destroy(PerDiemConfiguration $perDiemConfiguration): RedirectResponse
    {
        $this->configurations->delete($perDiemConfiguration);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Per diem configuration deleted.')]);

        return redirect()->route('per-diem-configurations.index');
    }

    public function manage(PerDiemConfiguration $perDiemConfiguration): Response
    {
        return Inertia::render('per-diem-configurations/manage', [
            'perDiemConfiguration' => $perDiemConfiguration,
            'mealRules' => $this->configurations->mealRules($perDiemConfiguration),
            'dailyRate' => $this->configurations->dailyRate($perDiemConfiguration),
        ]);
    }

    public function createMealRulePage(Request $request, PerDiemConfiguration $perDiemConfiguration): Response
    {
        $rules = $this->configurations->mealRules($perDiemConfiguration);
        $highestSortOrder = $rules->max('sort_order') ?? 0;

        return Inertia::render('per-diem-configurations/create-meal', [
            'perDiemConfiguration' => $perDiemConfiguration,
            'mealTypes' => $this->selectableMealTypes(),
            'nextSortOrder' => $request->integer('next_sort_order') ?: $highestSortOrder + 1,
        ]);
    }

    public function editMealRulePage(
        PerDiemConfiguration $perDiemConfiguration,
        PerDiemMealRule $perDiemMealRule,
    ): Response {
        return Inertia::render('per-diem-configurations/edit-meal', [
            'perDiemConfiguration' => $perDiemConfiguration,
            'mealRule' => $perDiemMealRule->load('mealTypeOption:id,code,name'),
            'mealTypes' => $this->selectableMealTypes($perDiemMealRule->meal_type),
        ]);
    }

    public function deleteMealRulePage(
        PerDiemConfiguration $perDiemConfiguration,
        PerDiemMealRule $perDiemMealRule,
    ): Response {
        return Inertia::render('per-diem-configurations/delete-meal', [
            'perDiemConfiguration' => $perDiemConfiguration,
            'mealRule' => $perDiemMealRule->load('mealTypeOption:id,code,name'),
            'dailyRate' => $this->configurations->dailyRate($perDiemConfiguration),
        ]);
    }

    public function storeMealRule(
        StorePerDiemMealRuleRequest $request,
        PerDiemConfiguration $perDiemConfiguration,
    ): RedirectResponse {
        $this->configurations->createMealRule($perDiemConfiguration, $this->mealRuleData($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meal rule created.')]);

        return back();
    }

    public function updateMealRule(
        UpdatePerDiemMealRuleRequest $request,
        PerDiemConfiguration $perDiemConfiguration,
        PerDiemMealRule $perDiemMealRule,
    ): RedirectResponse {
        $this->configurations->updateMealRule($perDiemMealRule, $this->mealRuleData($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meal rule updated.')]);

        return back();
    }

    public function destroyMealRule(
        PerDiemConfiguration $perDiemConfiguration,
        PerDiemMealRule $perDiemMealRule,
    ): RedirectResponse {
        $this->configurations->deleteMealRule($perDiemMealRule);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meal rule deleted.')]);

        return back();
    }

    /**
     * @return EloquentCollection<int, Location>
     */
    private function selectableLocations(): EloquentCollection
    {
        return Location::query()->active()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return EloquentCollection<int, MealType>
     */
    private function selectableMealTypes(?string $currentCode = null): EloquentCollection
    {
        return MealType::query()
            ->where(function ($query) use ($currentCode): void {
                $query->where('is_active', true);

                if ($currentCode !== null) {
                    $query->orWhere('code', $currentCode);
                }
            })
            ->ordered()
            ->get(['code', 'name', 'has_cutoff', 'is_active']);
    }

    /**
     * @return array<string, mixed>
     */
    private function mealRuleData(StorePerDiemMealRuleRequest $request): array
    {
        $data = $request->validated();
        $data['name'] = MealType::query()
            ->where('code', $data['meal_type'])
            ->value('name') ?? Str::headline((string) $data['meal_type']);

        return $data;
    }
}
