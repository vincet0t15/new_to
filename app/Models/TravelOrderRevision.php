<?php

namespace App\Models;

use App\Enums\TravelOrderChangeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One return-and-resubmission round of a travel order.
 *
 * The row is opened by the return so the previous content is captured before
 * the creator edits it. It is completed by the resubmission with the remarks,
 * change classification, and only the fields that actually changed.
 *
 * @property int $id
 * @property int $travel_order_id
 * @property int $revision_number
 * @property int|null $returned_by_user_id
 * @property Carbon|null $returned_at
 * @property string|null $return_remarks
 * @property int|null $revised_by_user_id
 * @property string|null $revision_remarks
 * @property TravelOrderChangeType $change_type
 * @property array<int, string>|null $changed_fields
 * @property array<string, mixed>|null $previous_data
 * @property array<string, mixed>|null $new_data
 * @property Carbon|null $resubmitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TravelOrder $travelOrder
 * @property-read User|null $returnedBy
 * @property-read User|null $revisedBy
 */
#[Fillable([
    'travel_order_id',
    'revision_number',
    'returned_by_user_id',
    'returned_at',
    'return_remarks',
    'revised_by_user_id',
    'revision_remarks',
    'change_type',
    'changed_fields',
    'previous_data',
    'new_data',
    'resubmitted_at',
])]
class TravelOrderRevision extends Model
{
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
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function revisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by_user_id');
    }

    /**
     * Whether the creator has answered this return.
     */
    public function isResubmitted(): bool
    {
        return $this->resubmitted_at !== null;
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
            'revision_number' => 'integer',
            'returned_by_user_id' => 'integer',
            'revised_by_user_id' => 'integer',
            'returned_at' => 'datetime',
            'resubmitted_at' => 'datetime',
            'change_type' => TravelOrderChangeType::class,
            'changed_fields' => 'array',
            'previous_data' => 'array',
            'new_data' => 'array',
        ];
    }
}
