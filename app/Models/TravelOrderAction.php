<?php

namespace App\Models;

use App\Enums\WorkflowEvent;
use Database\Factories\TravelOrderActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One audit-trail row for a travel order.
 *
 * @property int $id
 * @property int $travel_order_id
 * @property int|null $step_index
 * @property string|null $step_label
 * @property WorkflowEvent $event
 * @property int|null $revision_id
 * @property int|null $actor_user_id
 * @property string|null $remarks
 * @property bool $is_internal
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TravelOrder $travelOrder
 * @property-read User|null $actor
 */
#[Fillable([
    'travel_order_id',
    'step_index',
    'step_label',
    'event',
    'revision_id',
    'actor_user_id',
    'remarks',
    'is_internal',
    'meta',
])]
class TravelOrderAction extends Model
{
    /** @use HasFactory<TravelOrderActionFactory> */
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
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
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
            'revision_id' => 'integer',
            'actor_user_id' => 'integer',
            'event' => WorkflowEvent::class,
            'is_internal' => 'boolean',
            'meta' => 'array',
        ];
    }
}
