<?php

namespace App\Models;

use Database\Factories\OfficeDesignationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Who currently answers a step for an office.
 *
 * A designation belongs to the office and the reusable step type, not to one
 * workflow, so the same Budget Officer answers the budget step in both the
 * default and the Legislative chain. Deputies are second rows on the same step
 * and receive a copy alongside the primary holder; the first to act claims the
 * step, which is what keeps a leave of absence from stalling a document.
 *
 * @property int $id
 * @property int $office_id
 * @property int $workflow_step_type_id
 * @property int $employee_id
 * @property bool $is_deputy
 * @property bool $is_active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office $office
 * @property-read WorkflowStepType $stepType
 * @property-read Employee $employee
 */
#[Fillable([
    'office_id',
    'workflow_step_type_id',
    'employee_id',
    'is_deputy',
    'is_active',
    'starts_at',
    'ends_at',
])]
class OfficeDesignation extends Model
{
    /** @use HasFactory<OfficeDesignationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return BelongsTo<WorkflowStepType, $this>
     */
    public function stepType(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepType::class, 'workflow_step_type_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Designations that are switched on and whose effectivity window includes
     * today. A row with no dates is effective until somebody ends it.
     *
     * @param  Builder<OfficeDesignation>  $query
     * @return Builder<OfficeDesignation>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $today);
            })
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $today);
            });
    }

    /**
     * @param  Builder<OfficeDesignation>  $query
     * @return Builder<OfficeDesignation>
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_deputy', false);
    }

    /**
     * @param  Builder<OfficeDesignation>  $query
     * @return Builder<OfficeDesignation>
     */
    public function scopeDeputy(Builder $query): Builder
    {
        return $query->where('is_deputy', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'office_id' => 'integer',
            'workflow_step_type_id' => 'integer',
            'employee_id' => 'integer',
            'is_deputy' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }
}
