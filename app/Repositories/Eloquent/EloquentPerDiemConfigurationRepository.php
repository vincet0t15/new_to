<?php

namespace App\Repositories\Eloquent;

use App\Models\PerDiemConfiguration;
use App\Models\PerDiemMealRule;
use App\Repositories\Contracts\PerDiemConfigurationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EloquentPerDiemConfigurationRepository implements PerDiemConfigurationRepository
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = PerDiemConfiguration::query()
            ->with('locations:id,name')
            ->withSum('perDiemMealRules as calculated_daily_rate', 'amount')
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->transform(function (PerDiemConfiguration $configuration) {
            $rate = $configuration->calculated_daily_rate ?? $configuration->daily_rate ?? 0;
            $configuration->daily_rate = number_format((float) $rate, 2, '.', '');
            $configuration->setAttribute('usage_count', 0);
            unset($configuration->calculated_daily_rate);

            return $configuration;
        });

        return $paginator;
    }

    public function create(array $data): PerDiemConfiguration
    {
        return DB::transaction(function () use ($data): PerDiemConfiguration {
            $configuration = PerDiemConfiguration::create(Arr::except($data, ['location_ids']));
            $configuration->locations()->sync($data['location_ids'] ?? []);

            return $configuration;
        });
    }

    public function update(PerDiemConfiguration $configuration, array $data): PerDiemConfiguration
    {
        return DB::transaction(function () use ($configuration, $data): PerDiemConfiguration {
            $configuration->update(Arr::except($data, ['location_ids']));
            $configuration->locations()->sync($data['location_ids'] ?? []);

            return $configuration;
        });
    }

    public function delete(PerDiemConfiguration $configuration): void
    {
        $configuration->delete();
    }

    public function mealRules(PerDiemConfiguration $configuration): Collection
    {
        return $configuration->perDiemMealRules()->with('mealTypeOption:id,code,name')->get();
    }

    public function createMealRule(PerDiemConfiguration $configuration, array $data): PerDiemMealRule
    {
        $data['sort_order'] ??= $configuration->perDiemMealRules()->max('sort_order') + 1;

        return $configuration->perDiemMealRules()->create($data);
    }

    public function updateMealRule(PerDiemMealRule $mealRule, array $data): PerDiemMealRule
    {
        $mealRule->update($data);

        return $mealRule;
    }

    public function deleteMealRule(PerDiemMealRule $mealRule): void
    {
        $mealRule->delete();
    }

    public function dailyRate(PerDiemConfiguration $configuration): string
    {
        return number_format((float) $configuration->perDiemMealRules()->sum('amount'), 2, '.', '');
    }
}
