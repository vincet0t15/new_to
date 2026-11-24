<?php

namespace Database\Seeders;

use App\Concerns\ResolvesSeedCredentials;
use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates the one login the application cannot bootstrap without.
 *
 * Roles and permissions are seeded separately. An installation with no super
 * administrator has nobody who can reach the role editor, so this runs after
 * that seeder and gives its account the super administrator role.
 */
class SuperAdminSeeder extends Seeder
{
    use ResolvesSeedCredentials;
    use WithoutModelEvents;

    public function run(): void
    {
        $role = Role::firstWhere([
            'guard_name' => 'web',
            'slug' => SystemRole::SuperAdmin->value,
        ]);

        if ($role === null) {
            throw new RuntimeException(
                'The super administrator role is missing. Run RolesAndPermissionsSeeder before SuperAdminSeeder.'
            );
        }

        // Matched on the configured username, and the password is only applied
        // on creation, so re-seeding an existing database does not quietly hand
        // the administrator a new password.
        $username = (string) config('superadmin.username');

        $superAdmin = User::firstWhere(['username' => $username]) ?? User::create([
            'name' => (string) config('superadmin.name'),
            'username' => $username,
            'email' => config('superadmin.email'),
            'password' => $this->password(),
            'must_change_password' => $this->shouldForcePasswordChange(),
        ]);

        // Assigned by the role model rather than by name, because the role's
        // display name and its slug are not the same thing and only the slug is
        // guaranteed.
        $superAdmin->syncRoles([$role]);
    }

    /**
     * The super administrator's initial password.
     *
     * Read from configuration rather than from env() here, because env() returns
     * null once the configuration is cached: an administrator seeded on a
     * cached production install would silently get the development default.
     */
    private function password(): string
    {
        $password = config('superadmin.password');

        if (! is_string($password) || $password === '') {
            if (app()->isProduction()) {
                throw new RuntimeException(
                    'SUPERADMIN_PASSWORD must be set before seeding a production database.'
                );
            }

            return 'password';
        }

        return $password;
    }
}
