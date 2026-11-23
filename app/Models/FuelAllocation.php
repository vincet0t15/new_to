<?php

namespace App\Models;

use Database\Factories\FuelAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A fuel allocation/issuance budget record for an office.
 *
 * @property int $id
 * @property int $office_id
 * @property string $type
 * @property int|null $source_office_id
 * @property int|null $related_allocation_id
 * @property Carbon $allocation_date
 * @property string|null $reference_no
 * @property string|null $fuel_type
 * @property string $allocated_liters
 * @property string $allocated_amount
 * @property string|null $remarks
 * @property string $status
 * @property string|null $void_reason
 * @property Carbon|null $voided_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office $office
 * @property-read Office|null $sourceOffice
 * @property-read FuelAllocation|null $relatedAllocation
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
#[Fillable([
    'office_id',
    'type',
    'source_office_id',
    'related_allocation_id',
    'allocation_date',
    'reference_no',
    'fuel_type_id',
    'fuel_type',
    'allocated_liters',
    'allocated_amount',
    'remarks',
    'status',
    'void_reason',
    'voided_at',
    'created_by',
    'updated_by',
])]
class FuelAllocation extends Model
{
    /** @use HasFactory<FuelAllocationFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_VOIDED = 'voided';

    public const TYPE_ALLOCATION = 'allocation';

    public const TYPE_TRANSFER = 'transfer';

    protected function casts(): array
    {
        return [
            'allocation_date' => 'date',
            'allocated_liters' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function sourceOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'source_office_id');
    }

    /**
     * @return BelongsTo<FuelType, $this>
     */
    public function fuelTypeRecord(): BelongsTo
    {
        return $this->belongsTo(FuelType::class, 'fuel_type_id');
    }

    /**
     * @return BelongsTo<FuelAllocation, $this>
     */
    public function relatedAllocation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_allocation_id');
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
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<FuelAllocationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(FuelAllocationEvent::class)->latest();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
