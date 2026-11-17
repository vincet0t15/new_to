<?php

namespace App\Models;

use Database\Factories\WorkflowStepTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * What a step means, independently of any one workflow.
 *
 * The type owns the signature wording and whether the step is a signature or
 * only a receipt. A workflow step borrows those pieces, so "Mayor Approval"
 * reads and behaves the same in every chain that uses it while its target
 * office can still differ between workflows.
 *
 * @property int $id
 * @property string $name
 * @property string|null $signature_label
 * @property string|null $signature_title
 * @property bool $is_signatory
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, WorkflowStep> $steps
 * @property-read Collection<int, OfficeDesignation> $designations
 * @property-read int|null $steps_count
 * @property-read int|null $designations_count
 */
#[Fillable([
    'name',
    'signature_label',
    'signature_title',
    'is_signatory',
    'sort_order',
    'is_active',
])]
class WorkflowStepType extends Model
{
    /** @use HasFactory<WorkflowStepTypeFactory> */
    use HasFactory;

    /**
     * The steps in every workflow that use this type.
     *
     * @return HasMany<WorkflowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    /**
     * Who currently answers this step in each office.
     *
     * @return HasMany<OfficeDesignation, $this>
     */
    public function designations(): HasMany
    {
        return $this->hasMany(OfficeDesignation::class);
    }

    /**
     * A receipt step moves the document on without recording an approval.
     */
    public function isReceipt(): bool
    {
        return ! $this->is_signatory;
    }

    /**
     * @param  Builder<WorkflowStepType>  $query
     * @return Builder<WorkflowStepType>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<WorkflowStepType>  $query
     * @return Builder<WorkflowStepType>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_signatory' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
