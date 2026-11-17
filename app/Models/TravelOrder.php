<?php

namespace App\Models;

use App\Enums\TravelOrderDeliveryStatus;
use App\Enums\TravelOrderStatus;
use Database\Factories\TravelOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property string|null $submission_notes
 * @property string|null $other_expenses
 * @property string|null $destination
 * @property Carbon|null $date_from
 * @property Carbon|null $date_to
 * @property int|null $travel_classification_id
 * @property int|null $fund_source_id
 * @property int|null $origin_id
 * @property int|null $destination_id
 * @property int|null $official_station_id
 * @property string|null $fund_source
 * @property string|null $travel_classification
 * @property TravelOrderStatus $status
 * @property int $revision_number
 * @property int|null $returned_by_user_id
 * @property int|null $returned_from_step_index
 * @property string|null $returned_from_step
 * @property string|null $return_remarks
 * @property Carbon|null $returned_at
 * @property Carbon|null $last_resubmitted_at
 * @property array<int, int>|null $routed_steps
 * @property int|null $current_step_index
 * @property int|null $recipient_user_id
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee|null $employee
 * @property-read Office|null $office
 * @property-read TravelClassification|null $travelClassification
 * @property-read FundSource|null $fundSource
 * @property-read Location|null $origin
 * @property-read Location|null $destinationLocation
 * @property-read Location|null $officialStation
 * @property-read Collection<int, ExpenseType> $expenseTypes
 * @property-read Collection<int, TransportationMode> $transportationModes
 * @property-read User $creator
 * @property-read User|null $returnedBy
 * @property-read Workflow|null $workflow
 * @property-read Collection<int, TravelOrderDelivery> $deliveries
 * @property-read Collection<int, TravelOrderAction> $actions
 * @property-read Collection<int, TravelOrderRevision> $revisions
 */
#[Fillable([
    'control_no',
    'to_number',
    'employee_id',
    'office_id',
    'created_by',
    'workflow_id',
    'purpose',
    'submission_notes',
    'other_expenses',
    'destination',
    'date_from',
    'date_to',
    'travel_classification_id',
    'fund_source_id',
    'origin_id',
    'destination_id',
    'official_station_id',
    'fund_source',
    'travel_classification',
    'status',
    'revision_number',
    'returned_by_user_id',
    'returned_from_step_index',
    'returned_from_step',
    'return_remarks',
    'returned_at',
    'last_resubmitted_at',
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
     * @return BelongsTo<TravelClassification, $this>
     */
    public function travelClassification(): BelongsTo
    {
        return $this->belongsTo(TravelClassification::class);
    }

    /**
     * @return BelongsTo<FundSource, $this>
     */
    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_id');
    }

    /**
     * The destination lookup. Named destinationLocation so it does not clash
     * with the legacy destination text column.
     *
     * @return BelongsTo<Location, $this>
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function officialStation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'official_station_id');
    }

    /**
     * @return BelongsToMany<ExpenseType, $this>
     */
    public function expenseTypes(): BelongsToMany
    {
        return $this->belongsToMany(ExpenseType::class, 'travel_order_expense_type')
            ->withTimestamps()
            ->orderBy('name');
    }

    /**
     * @return BelongsToMany<TransportationMode, $this>
     */
    public function transportationModes(): BelongsToMany
    {
        return $this->belongsToMany(TransportationMode::class, 'travel_order_transportation_mode')
            ->withTimestamps()
            ->orderBy('name');
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
     * The approver who most recently sent the order back for revision.
     *
     * @return BelongsTo<User, $this>
     */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    /**
     * Every return-and-resubmission round, oldest first.
     *
     * @return HasMany<TravelOrderRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(TravelOrderRevision::class)->orderBy('revision_number');
    }

    /**
     * The revision row opened by the most recent return, if any.
     */
    public function latestRevision(): ?TravelOrderRevision
    {
        return $this->revisions()->orderByDesc('revision_number')->first();
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
        return $this->deliveries()->whereIn('status', [
            TravelOrderDeliveryStatus::Pending->value,
            TravelOrderDeliveryStatus::Received->value,
        ]);
    }

    /**
     * The copy this account can still act on at the current step.
     *
     * A delivery that was closed when somebody else claimed the step is not
     * actionable, which is what makes the first-to-act rule visible to the
     * service layer rather than only to the inbox query.
     */
    public function activeDeliveryFor(User $user): ?TravelOrderDelivery
    {
        if ($this->current_step_index === null) {
            return null;
        }

        return $this->deliveries()
            ->where('step_index', $this->current_step_index)
            ->where('user_id', $user->id)
            ->open()
            ->first();
    }

    /**
     * Whether this account still holds the current step.
     */
    public function holdsActiveDeliveryFor(User $user): bool
    {
        return $this->activeDeliveryFor($user) !== null;
    }

    /**
     * Whether this account created the travel order.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    /**
     * Whether the order is sitting with its creator for revision.
     */
    public function isReturned(): bool
    {
        return $this->status === TravelOrderStatus::Returned;
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
            'revision_number' => 'integer',
            'returned_by_user_id' => 'integer',
            'returned_from_step_index' => 'integer',
            'travel_classification_id' => 'integer',
            'fund_source_id' => 'integer',
            'origin_id' => 'integer',
            'destination_id' => 'integer',
            'official_station_id' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
            'status' => TravelOrderStatus::class,
            'routed_steps' => 'array',
            'current_step_index' => 'integer',
            'submitted_at' => 'datetime',
            'returned_at' => 'datetime',
            'last_resubmitted_at' => 'datetime',
        ];
    }
}
