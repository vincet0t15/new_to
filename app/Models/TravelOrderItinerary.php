<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One leg of a travel order's itinerary.
 *
 * The automatic flags record how the fare and per diem were produced. A row
 * with per_diem_is_auto computes its amount from the per diem configuration
 * that covers the destination; a manual row keeps the amount the filer wrote.
 *
 * @property int $id
 * @property int $travel_order_id
 * @property Carbon $date_from
 * @property Carbon $date_to
 * @property string $departure_time
 * @property string|null $arrival_time
 * @property int|null $from_location_id
 * @property int|null $to_location_id
 * @property string $activity
 * @property string|null $reason
 * @property int|null $transportation_mode_id
 * @property string|null $transportation_fare
 * @property bool $transportation_fare_is_auto
 * @property string|null $per_diem
 * @property bool $per_diem_is_auto
 * @property string|null $registration_fee
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TravelOrder $travelOrder
 * @property-read Location|null $fromLocation
 * @property-read Location|null $toLocation
 * @property-read TransportationMode|null $transportationMode
 */
#[Fillable([
    'travel_order_id',
    'date_from',
    'date_to',
    'departure_time',
    'arrival_time',
    'from_location_id',
    'to_location_id',
    'activity',
    'reason',
    'transportation_mode_id',
    'transportation_fare',
    'transportation_fare_is_auto',
    'per_diem',
    'per_diem_is_auto',
    'registration_fee',
])]
class TravelOrderItinerary extends Model
{
    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'from_location_id' => 'integer',
            'to_location_id' => 'integer',
            'transportation_mode_id' => 'integer',
            'transportation_fare' => 'decimal:2',
            'transportation_fare_is_auto' => 'boolean',
            'per_diem' => 'decimal:2',
            'per_diem_is_auto' => 'boolean',
            'registration_fee' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<TravelOrder, $this>
     */
    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * @return BelongsTo<TransportationMode, $this>
     */
    public function transportationMode(): BelongsTo
    {
        return $this->belongsTo(TransportationMode::class);
    }

    /**
     * The amount this leg claims for transportation, per diem and registration.
     */
    public function lineTotal(): float
    {
        return (float) $this->transportation_fare
            + (float) $this->per_diem
            + (float) $this->registration_fee;
    }
}
