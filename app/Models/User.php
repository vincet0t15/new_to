<?php

namespace App\Models;

use App\Enums\SystemRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Permission> $permissions
 */
#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Whether this account holds an administrative role.
     */
    public function isAdministrator(): bool
    {
        return $this->hasAnyRoleSlug([
            SystemRole::Admin->value,
            SystemRole::SuperAdmin->value,
        ]);
    }

    /**
     * Whether this account holds the highest administrative role.
     */
    public function isSuperAdministrator(): bool
    {
        return $this->hasRoleSlug(SystemRole::SuperAdmin->value);
    }

    /**
     * Whether this account holds a role with the given slug.
     *
     * Spatie matches roles by name, while this app uses the slug as the stable
     * identity because administrators may rename a role without breaking the
     * behaviour keyed to it.
     */
    public function hasRoleSlug(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    /**
     * @param  array<int, string>  $slugs
     */
    public function hasAnyRoleSlug(array $slugs): bool
    {
        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    /**
     * The role that decides which portal the account lands in.
     *
     * A person may hold several roles; the most privileged one wins so the
     * portal and the work it exposes agree with each other.
     */
    public function primaryRoleSlug(): ?string
    {
        $priority = [
            SystemRole::SuperAdmin->value,
            SystemRole::Admin->value,
            SystemRole::Hrmo->value,
            SystemRole::Mayor->value,
            SystemRole::BudgetOfficer->value,
            SystemRole::DepartmentHead->value,
            SystemRole::Employee->value,
        ];

        $roles = $this->roles->pluck('slug')->all();

        foreach ($priority as $slug) {
            if (in_array($slug, $roles, true)) {
                return $slug;
            }
        }

        return $roles[0] ?? null;
    }
}
