<?php

namespace App\Models;

use App\Enums\TravelOrderDeliveryStatus;
use Database\Factories\TravelOrderDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One person's copy of a travel order at one step.
 *
 * @property int $id
 * @property int $travel_order_id
 * @property int $step_index
 * @property int|null $step_type_id
 * @property int $user_id
 * @property TravelOrderDeliveryStatus $status
 * @property Carbon|null $received_at
 * @property Carbon|null $acted_at
 * @property string|null $acted_via
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TravelOrder $travelOrder
 * @property-read User|null $user
 * @property-read WorkflowStepType|null $stepType
 */
#[Fillable([
    'travel_order_id',
    'step_index',
    'step_type_id',
    'user_id',
    'status',
    'received_at',
    'acted_at',
    'acted_via',
])]
class TravelOrderDelivery extends Model
{
    /** @use HasFactory<TravelOrderDeliveryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<TravelOrder, $this>
     */
    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<WorkflowStepType, $this>
     */
    public function stepType(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepType::class, 'step_type_id');
    }

    /**
     * @param  Builder<TravelOrderDelivery>  $query
     * @return Builder<TravelOrderDelivery>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TravelOrderDeliveryStatus::Pending->value,
            TravelOrderDeliveryStatus::Received->value,
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'travel_order_id' => 'integer',
            'step_index' => 'integer',
            'step_type_id' => 'integer',
            'user_id' => 'integer',
            'status' => TravelOrderDeliveryStatus::class,
            'received_at' => 'datetime',
            'acted_at' => 'datetime',
        ];
    }
}
