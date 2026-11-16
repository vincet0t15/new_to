<?php

namespace App\Models;

use Database\Factories\WorkflowStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One leg of a named approval chain.
 *
 * The step type supplies the meaning and signature wording; this row supplies
 * the position in the chain and the office that answers it. A null office is
 * deliberate: it makes the step follow the office that filed the travel order,
 * which is how every office gets its own department head without a step per
 * department.
 *
 * @property int $id
 * @property int $workflow_id
 * @property int $workflow_step_type_id
 * @property string|null $label
 * @property int|null $office_id
 * @property int $sort_order
 * @property bool $auto_skip_creator
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workflow $workflow
 * @property-read WorkflowStepType $stepType
 * @property-read Office|null $office
 */
#[Fillable([
    'workflow_id',
    'workflow_step_type_id',
    'label',
    'office_id',
    'sort_order',
    'auto_skip_creator',
    'is_active',
])]
class WorkflowStep extends Model
{
    /** @use HasFactory<WorkflowStepFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return BelongsTo<WorkflowStepType, $this>
     */
    public function stepType(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepType::class, 'workflow_step_type_id');
    }

    /**
     * The office that answers the step, or null when the filing office does.
     *
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * The label the preview and printed forms should show.
     */
    public function displayLabel(): string
    {
        return $this->label ?: $this->stepType->name;
    }

    /**
     * Whether this step follows the office that filed the travel order.
     */
    public function followsFilingOffice(): bool
    {
        return $this->office_id === null;
    }

    /**
     * @param  Builder<WorkflowStep>  $query
     * @return Builder<WorkflowStep>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<WorkflowStep>  $query
     * @return Builder<WorkflowStep>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
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
            'sort_order' => 'integer',
            'auto_skip_creator' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
