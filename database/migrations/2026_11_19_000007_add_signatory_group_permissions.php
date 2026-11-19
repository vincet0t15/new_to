<?php

use App\Enums\SystemRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * The permissions that gate the two signatory configuration screens.
     *
     * Kept identical to the list in RolesAndPermissionsSeeder. They are written
     * here as well so an existing database picks them up on migrate rather than
     * needing the seeder run by hand.
     *
     * @var list<string>
     */
    private array $permissions = [
        'signatory-groups.view',
        'signatory-groups.create',
        'signatory-groups.update',
        'signatory-groups.delete',
        'itinerary-signatory-groups.view',
        'itinerary-signatory-groups.create',
        'itinerary-signatory-groups.update',
        'itinerary-signatory-groups.delete',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // A fresh install runs this migration before the seeder has created
        // the roles, so the grant is skipped there and the seeder does it. An
        // existing install already has the roles, which is the case this grant
        // is here for.
        $roles = Role::query()
            ->whereIn('slug', [SystemRole::Admin->value, SystemRole::SuperAdmin->value])
            ->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($this->permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()->whereIn('name', $this->permissions)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
