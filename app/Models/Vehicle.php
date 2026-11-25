<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A registered government vehicle.
 *
 * This is the fleet registry trip tickets select from. The ticket copies the
 * plate, type and driver onto itself when it is prepared, so a printed ticket
 * keeps what it carried even if the registry changes later.
 *
 * @property int $id
 * @property string $plate_no
 * @property string|null $vehicle_type
 * @property string|null $model
 * @property int|null $capacity
 * @property int|null $driver_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee|null $driver
 */
#[Fillable([
    'plate_no',
    'vehicle_type',
    'model',
    'capacity',
    'driver_id',
    'is_active',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @param  Builder<Vehicle>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    /**
     * @return HasMany<TripTicket, $this>
     */
    public function tripTickets(): HasMany
    {
        return $this->hasMany(TripTicket::class);
    }
}
