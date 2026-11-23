<?php

namespace App\Models;

use Database\Factories\FuelTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_active'])]
class FuelType extends Model
{
    /** @use HasFactory<FuelTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<FuelType>  $query
     * @return Builder<FuelType>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<TripTicket, $this>
     */
    public function tripTickets(): HasMany
    {
        return $this->hasMany(TripTicket::class);
    }

    /**
     * @return HasMany<FuelAllocation, $this>
     */
    public function fuelAllocations(): HasMany
    {
        return $this->hasMany(FuelAllocation::class);
    }
}
