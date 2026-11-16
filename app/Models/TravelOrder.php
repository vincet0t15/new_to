<?php

namespace App\Models;

use App\Enums\TravelOrderStatus;
use Database\Factories\TravelOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A travel order and its routing state.
 *
 * routed_steps is the frozen list of workflow step ids captured at submission.
 * current_step_index points into that list. Deliveries hold who currently
 * answers the step, and actions hold the immutable audit trail.
 *
 * @property int $id
 * @property string|null $control_no
 * @property string|null $to_number
 * @property int|null $employee_id
 * @property int|null $office_id
 * @property int $created_by
 * @property int|null $workflow_id
 * @property string|null $purpose
 * @property string|null $destination
 * @property Carbon|null $date_from
 * @property Carbon|null $date_to
 * @property string|null $fund_source
 * @property string|null $travel_classification
 * @property TravelOrderStatus $status
 * @property array<int, int>|null $routed_steps
 * @property int|null $current_step_index
 * @property int|null $recipient_user_id
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee|null $employee
 * @property-read Office|null $office
 * @property-read User $creator
 * @property-read Workflow|null $workflow
 * @property-read Collection<int, TravelOrderDelivery> $deliveries
 * @property-read Collection<int, TravelOrderAction> $actions
 */
#[Fillable([
    'control_no',
    'to_number',
    'employee_id',
    'office_id',
    'created_by',
    'workflow_id',
    'purpose',
    'destination',
    'date_from',
    'date_to',
    'fund_source',
    'travel_classification',
    'status',
    'routed_steps',
    'current_step_index',
    'recipient_user_id',
    'submitted_at',
])]
class TravelOrder extends Model
{
    /** @use HasFactory<TravelOrderFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * @return HasMany<TravelOrderDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(TravelOrderDelivery::class);
    }

    /**
     * @return HasMany<TravelOrderAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(TravelOrderAction::class);
    }

    /**
     * Open deliveries at the current step.
     *
     * @return HasMany<TravelOrderDelivery, $this>
     */
    public function openDeliveries(): HasMany
    {
        return $this->deliveries()->where('status', 'pending');
    }

    /**
     * @param  Builder<TravelOrder>  $query
     * @return Builder<TravelOrder>
     */
    public function scopeForCreator(Builder $query, User $user): Builder
    {
        return $query->where('created_by', $user->id);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'office_id' => 'integer',
            'created_by' => 'integer',
            'workflow_id' => 'integer',
            'recipient_user_id' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
            'status' => TravelOrderStatus::class,
            'routed_steps' => 'array',
            'current_step_index' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }
}
