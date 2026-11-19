<?php

namespace App\Models;

use Database\Factories\OfficeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A department or office in the organization.
 *
 * The optional workflow is the chain used when one of its employees files a
 * travel order. A null workflow is the normal case and means the default chain
 * applies.
 *
 * @property int $id
 * @property string $office_name
 * @property string|null $office_code
 * @property int|null $workflow_id
 * @property int|null $signatory_group_id
 * @property int|null $itinerary_signatory_group_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workflow|null $workflow
 * @property-read Collection<int, Employee> $employees
 * @property-read Collection<int, WorkflowStep> $workflowSteps
 * @property-read Collection<int, OfficeDesignation> $designations
 * @property-read SignatoryGroup|null $signatoryGroup
 * @property-read ItinerarySignatoryGroup|null $itinerarySignatoryGroup
 */
#[Fillable(['office_name', 'office_code', 'workflow_id', 'signatory_group_id', 'itinerary_signatory_group_id'])]
class Office extends Model
{
    /** @use HasFactory<OfficeFactory> */
    use HasFactory;

    /**
     * The employees assigned to this office.
     *
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * The override chain used when this office files a travel order.
     *
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * The workflow steps that name this office as their target.
     *
     * @return HasMany<WorkflowStep, $this>
     */
    public function workflowSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    /**
     * The people designated to answer steps for this office.
     *
     * @return HasMany<OfficeDesignation, $this>
     */
    public function designations(): HasMany
    {
        return $this->hasMany(OfficeDesignation::class);
    }

    /**
     * The reusable signatory group this office's travel orders print.
     *
     * Null means every signature line keeps the signer its designation
     * resolves to.
     *
     * @return BelongsTo<SignatoryGroup, $this>
     */
    public function signatoryGroup(): BelongsTo
    {
        return $this->belongsTo(SignatoryGroup::class);
    }

    /**
     * The reusable itinerary signatory group this office's Appendix A prints.
     *
     * Null means the two lines print their standard role titles over an empty
     * name.
     *
     * @return BelongsTo<ItinerarySignatoryGroup, $this>
     */
    public function itinerarySignatoryGroup(): BelongsTo
    {
        return $this->belongsTo(ItinerarySignatoryGroup::class);
    }
}
