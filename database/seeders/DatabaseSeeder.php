<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // The starter account is an administrator so a fresh environment has
        // a way into the administration portal without editing the database.
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $user->assignRole(
            Role::query()
                ->where('guard_name', 'web')
                ->where('slug', SystemRole::SuperAdmin->value)
                ->firstOrFail()
        );
    }
}
