<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_active'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<Location>  $q
     * @return Builder<Location>
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * @return HasMany<TransportationFare, $this>
     */
    public function transportationFares(): HasMany
    {
        return $this->hasMany(TransportationFare::class);
    }

    /**
     * @return BelongsToMany<PerDiemConfiguration, $this>
     */
    public function perDiemConfigurations(): BelongsToMany
    {
        return $this->belongsToMany(PerDiemConfiguration::class, 'per_diem_configuration_location');
    }

    /**
     * @return HasMany<TravelOrder, $this>
     */
    public function originTravelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class, 'origin_id');
    }

    /**
     * @return HasMany<TravelOrder, $this>
     */
    public function destinationTravelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class, 'destination_id');
    }

    /**
     * @return HasMany<TravelOrder, $this>
     */
    public function officialStationTravelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class, 'official_station_id');
    }
}
