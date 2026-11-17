<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'has_cutoff', 'is_active', 'sort_order'])]
class MealType extends Model
{
    protected function casts(): array
    {
        return [
            'has_cutoff' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<MealType>  $query
     * @return Builder<MealType>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<MealType>  $query
     * @return Builder<MealType>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<PerDiemMealRule, $this>
     */
    public function perDiemMealRules(): HasMany
    {
        return $this->hasMany(PerDiemMealRule::class, 'meal_type', 'code');
    }
}
