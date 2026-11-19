<?php

namespace App\Models;

use Database\Factories\PerDiemConfigurationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_until
 * @property string|null $daily_rate
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'effective_from', 'effective_until', 'daily_rate', 'is_active'])]
class PerDiemConfiguration extends Model
{
    /** @use HasFactory<PerDiemConfigurationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date', 'daily_rate' => 'decimal:2', 'is_active' => 'boolean'];
    }

    /**
     * @param  Builder<PerDiemConfiguration>  $q
     * @return Builder<PerDiemConfiguration>
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'per_diem_configuration_location');
    }

    /**
     * @return HasMany<PerDiemMealRule, $this>
     */
    public function perDiemMealRules(): HasMany
    {
        return $this->hasMany(PerDiemMealRule::class)->orderBy('sort_order')->orderBy('id');
    }
}
