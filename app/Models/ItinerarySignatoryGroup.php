<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reusable set of the Itinerary of Travel's (Appendix A) two signature lines.
 *
 * The Appendix A sheet prints CERTIFIED/REVIEWED BY and APPROVED BY, so unlike
 * a travel order signatory group there is no OIC line.
 *
 * @property int $id
 * @property string $name
 * @property int|null $certified_employee_id
 * @property string|null $certified_title
 * @property int|null $approved_employee_id
 * @property string|null $approved_title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee|null $certifiedEmployee
 * @property-read Employee|null $approvedEmployee
 * @property-read Collection<int, Office> $offices
 */
#[Fillable([
    'name',
    'certified_employee_id',
    'certified_title',
    'approved_employee_id',
    'approved_title',
])]
class ItinerarySignatoryGroup extends Model
{
    /**
     * The employee named to sign the CERTIFIED/REVIEWED line, when one is set.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function certifiedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'certified_employee_id');
    }

    /**
     * The employee named to sign the APPROVED line, when one is set.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function approvedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_employee_id');
    }

    /**
     * The offices whose Appendix A uses this group.
     *
     * @return HasMany<Office, $this>
     */
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class, 'itinerary_signatory_group_id')
            ->orderBy('office_name');
    }
}
