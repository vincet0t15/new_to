<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $suffix
 * @property string $full_name
 * @property int|null $position_id
 * @property int $office_id
 * @property int $employment_type_id
 * @property string|null $contact_number
 * @property string|null $monthly_salary
 * @property string|null $image_path
 * @property string|null $photo_url
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office|null $office
 * @property-read Position|null $position
 * @property-read EmploymentType|null $employmentType
 * @property-read User|null $user
 */
#[Fillable([
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'position_id',
    'office_id',
    'employment_type_id',
    'contact_number',
    'monthly_salary',
    'image_path',
    'is_active',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['full_name', 'photo_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'monthly_salary' => 'decimal:2',
        ];
    }

    /**
     * The employee's name as one printable string.
     */
    public function getFullNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])->filter()->implode(' ');
    }

    /**
     * The publicly reachable URL of the employee's photo.
     *
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->image_path
                ? Storage::disk('public')->url($this->image_path)
                : null
        );
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * The employee's position, when one has been recorded.
     *
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * The employee's position name, or an empty string when none is recorded.
     */
    public function positionName(): string
    {
        $name = $this->position->name ?? null;

        return is_string($name) ? $name : '';
    }

    /**
     * @return BelongsTo<EmploymentType, $this>
     */
    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    /**
     * The login that receives travel orders for this employee.
     *
     * An employee can exist without an account; the current holders of an
     * approval step are designations, and delivery still targets a login.
     *
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Whether this employee can be listed as a traveler.
     */
    public function canBeTraveler(): bool
    {
        return $this->is_active;
    }

    /**
     * Whether a travel order can be routed to this employee.
     *
     * Delivery targets a user record, so the employee needs a login as well.
     */
    public function canReceiveTravelOrders(): bool
    {
        return $this->canBeTraveler() && $this->user !== null;
    }
}
