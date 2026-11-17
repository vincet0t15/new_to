<?php

namespace App\Repositories\Contracts;

use App\Models\PerDiemConfiguration;
use App\Models\PerDiemMealRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PerDiemConfigurationRepository
{
    /**
     * @return LengthAwarePaginator<int, PerDiemConfiguration>
     */
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PerDiemConfiguration;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PerDiemConfiguration $configuration, array $data): PerDiemConfiguration;

    public function delete(PerDiemConfiguration $configuration): void;

    /**
     * @return Collection<int, PerDiemMealRule>
     */
    public function mealRules(PerDiemConfiguration $configuration): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createMealRule(PerDiemConfiguration $configuration, array $data): PerDiemMealRule;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMealRule(PerDiemMealRule $mealRule, array $data): PerDiemMealRule;

    public function deleteMealRule(PerDiemMealRule $mealRule): void;

    public function dailyRate(PerDiemConfiguration $configuration): string;
}
