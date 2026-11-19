<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reusable set of the travel order's three printed signature lines.
 *
 * An office names one group, and the printed travel order takes its CERTIFIED,
 * APPROVED, and OIC signers from it. A blank half of the group leaves that half
 * as the designation resolved it.
 *
 * @property int $id
 * @property string $name
 * @property int|null $certified_signatory_employee_id
 * @property string|null $certified_signatory_title
 * @property int|null $approved_signatory_employee_id
 * @property string|null $approved_signatory_title
 * @property int|null $oic_signatory_employee_id
 * @property string|null $oic_signatory_title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee|null $certifiedSignatory
 * @property-read Employee|null $approvedSignatory
 * @property-read Employee|null $oicSignatory
 * @property-read Collection<int, Office> $offices
 */
#[Fillable([
    'name',
    'certified_signatory_employee_id',
    'certified_signatory_title',
    'approved_signatory_employee_id',
    'approved_signatory_title',
    'oic_signatory_employee_id',
    'oic_signatory_title',
])]
class SignatoryGroup extends Model
{
    /**
     * The employee named to sign the CERTIFIED line, when one is set.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function certifiedSignatory(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'certified_signatory_employee_id');
    }

    /**
     * The employee named to sign the APPROVED line, when one is set.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function approvedSignatory(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_signatory_employee_id');
    }

    /**
     * The employee named as the OIC, when one is set.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function oicSignatory(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'oic_signatory_employee_id');
    }

    /**
     * The offices whose printed travel orders use this group.
     *
     * @return HasMany<Office, $this>
     */
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class)->orderBy('office_name');
    }
}
