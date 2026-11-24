<?php

namespace App\Concerns;

use RuntimeException;

/**
 * Shared policy for the seeders that create login accounts.
 *
 * Credentials are read from configuration so cached production config still
 * resolves them. Existing accounts are never changed by a seeder; these helpers
 * only decide the initial password and the first-login flag of a new account.
 */
trait ResolvesSeedCredentials
{
    /**
     * The initial password for a seeded account.
     */
    protected function seededPassword(string $configKey, string $envName): string
    {
        $password = config($configKey);

        if (! is_string($password) || $password === '') {
            if (app()->isProduction()) {
                throw new RuntimeException(
                    $envName.' must be set before seeding a production database.'
                );
            }

            return 'password';
        }

        return $password;
    }

    /**
     * Whether newly seeded accounts must change their password at first login.
     */
    protected function shouldForcePasswordChange(): bool
    {
        $flag = config('seed-credentials.force_password_change');

        if (is_bool($flag)) {
            return $flag;
        }

        if (is_string($flag) && $flag !== '') {
            return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
        }

        return ! app()->environment(['local', 'testing']);
    }
}
