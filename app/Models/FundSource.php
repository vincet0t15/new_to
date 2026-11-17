<?php

namespace App\Models;

use Database\Factories\FundSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_active'])]
class FundSource extends Model
{
    /** @use HasFactory<FundSourceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<FundSource>  $q
     * @return Builder<FundSource>
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * @return HasMany<TravelOrder, $this>
     */
    public function travelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class);
    }
}
