<?php

namespace App\Models;

use App\Enums\TripTicketStatus;
use Database\Factories\TripTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * The trip ticket prepared for a travel order that uses a government vehicle.
 *
 * The vehicle details live on this row rather than in a fleet registry: the
 * plate, type, fuel and efficiency are exactly what the printed ticket needs,
 * and the driver is an employee record. The liters are the estimated
 * requirement, computed from the one-way distance, the round-trip flag and the
 * efficiency on file.
 *
 * @property int $id
 * @property int $travel_order_id
 * @property TripTicketStatus $status
 * @property string $vehicle_plate_no
 * @property string|null $vehicle_type
 * @property int|null $driver_id
 * @property string|null $fuel_type
 * @property int|null $fuel_source_office_id
 * @property string|null $fuel_efficiency
 * @property string $distance_km
 * @property bool $round_trip
 * @property string|null $odometer_start
 * @property string|null $odometer_end
 * @property string $liters
 * @property string|null $fuel_price_per_liter
 * @property string|null $fuel_cost
 * @property string|null $fuel_station
 * @property string|null $fuel_receipt_no
 * @property string|null $fuel_receipt_path
 * @property string|null $fuel_consumed
 * @property string|null $remarks
 * @property string|null $ticket_no
 * @property int|null $created_by
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property Carbon|null $cancelled_at
 * @property string|null $cancel_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TravelOrder $travelOrder
 * @property-read Employee|null $driver
 * @property-read User|null $creator
 * @property-read User|null $completer
 */
#[Fillable([
    'travel_order_id',
    'status',
    'vehicle_plate_no',
    'vehicle_type',
    'driver_id',
    'fuel_type_id',
    'fuel_type',
    'fuel_source_office_id',
    'fuel_efficiency',
    'distance_km',
    'round_trip',
    'odometer_start',
    'odometer_end',
    'liters',
    'fuel_price_per_liter',
    'fuel_cost',
    'fuel_station',
    'fuel_receipt_no',
    'fuel_receipt_path',
    'fuel_consumed',
    'remarks',
    'ticket_no',
    'created_by',
    'completed_at',
    'completed_by',
    'cancelled_at',
    'cancel_reason',
])]
class TripTicket extends Model
{
    /** @use HasFactory<TripTicketFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['fuel_receipt_url'];

    protected function casts(): array
    {
        return [
            'status' => TripTicketStatus::class,
            'round_trip' => 'boolean',
            'fuel_efficiency' => 'decimal:2',
            'distance_km' => 'decimal:2',
            'odometer_start' => 'decimal:2',
            'odometer_end' => 'decimal:2',
            'liters' => 'decimal:2',
            'fuel_price_per_liter' => 'decimal:2',
            'fuel_cost' => 'decimal:2',
            'fuel_consumed' => 'decimal:2',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<Employee, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function fuelSourceOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'fuel_source_office_id');
    }

    /**
     * @return BelongsTo<FuelType, $this>
     */
    public function fuelTypeRecord(): BelongsTo
    {
        return $this->belongsTo(FuelType::class, 'fuel_type_id');
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isCompleted(): bool
    {
        return $this->status === TripTicketStatus::Completed;
    }

    public function isCancelled(): bool
    {
        return $this->status === TripTicketStatus::Cancelled;
    }

    public function getFuelReceiptUrlAttribute(): ?string
    {
        if (! $this->fuel_receipt_path) {
            return null;
        }

        return Storage::disk('public')->url($this->fuel_receipt_path);
    }
}
