<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'administration.view',
            'offices.view',
            'offices.create',
            'offices.update',
            'offices.delete',
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
            'accounts.view',
            'accounts.edit',
            'accounts.manage-admins',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
            'workflow.manage',
            'workflow.delete',
            'office-designations.view',
            'office-designations.create',
            'office-designations.update',
            'office-designations.delete',
            'travel-orders.view',
            'travel-orders.view-all',
            'travel-orders.create',
            'travel-orders.update',
            'travel-orders.delete',
            'travel-orders.send',
            'travel-orders.approve',
            'travel-orders.return',
            'travel-orders.forward',
            'travel-orders.resubmit',
            'travel-orders.cancel',
            'travel-orders.earmark',
            'travel-orders.print',
            'travel-orders.attach',
            'drafts.view',
            'drafts.update',
            'drafts.delete',
            'inbox.view',
            'inbox.receive',
            'sent.view',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            SystemRole::SuperAdmin->value => [
                'description' => 'Super Admin',
                'permissions' => $permissions,
            ],
            SystemRole::Admin->value => [
                'description' => 'Admin',
                'permissions' => array_values(array_diff($permissions, [
                    'accounts.manage-admins',
                    'workflow.delete',
                ])),
            ],
            SystemRole::Employee->value => [
                'description' => 'Employee',
                'permissions' => [
                    'offices.view',
                    'travel-orders.view',
                    'travel-orders.create',
                    'travel-orders.update',
                    'travel-orders.delete',
                    'travel-orders.send',
                    'travel-orders.resubmit',
                    'travel-orders.print',
                    'travel-orders.attach',
                    'drafts.view',
                    'drafts.update',
                    'drafts.delete',
                    'inbox.view',
                    'inbox.receive',
                    'sent.view',
                ],
            ],
            SystemRole::DepartmentHead->value => [
                'description' => 'Department Head',
                'permissions' => [
                    'offices.view',
                    'employees.view',
                    'travel-orders.view',
                    'travel-orders.approve',
                    'travel-orders.return',
                    'travel-orders.forward',
                    'travel-orders.print',
                    'travel-orders.attach',
                    'inbox.view',
                    'inbox.receive',
                    'sent.view',
                ],
            ],
            SystemRole::BudgetOfficer->value => [
                'description' => 'Budget Officer',
                'permissions' => [
                    'offices.view',
                    'travel-orders.view',
                    'travel-orders.approve',
                    'travel-orders.return',
                    'travel-orders.forward',
                    'travel-orders.earmark',
                    'travel-orders.print',
                    'travel-orders.attach',
                    'inbox.view',
                    'inbox.receive',
                    'sent.view',
                ],
            ],
            SystemRole::Mayor->value => [
                'description' => "Mayor's Office",
                'permissions' => [
                    'offices.view',
                    'travel-orders.view',
                    'travel-orders.approve',
                    'travel-orders.return',
                    'travel-orders.forward',
                    'travel-orders.print',
                    'travel-orders.attach',
                    'inbox.view',
                    'inbox.receive',
                    'sent.view',
                ],
            ],
            SystemRole::Hrmo->value => [
                'description' => 'HRMO',
                'permissions' => [
                    'offices.view',
                    'employees.view',
                    'travel-orders.view',
                    'travel-orders.approve',
                    'travel-orders.return',
                    'travel-orders.forward',
                    'travel-orders.print',
                    'travel-orders.attach',
                    'inbox.view',
                    'inbox.receive',
                    'sent.view',
                ],
            ],
        ];

        foreach ($roles as $slug => $config) {
            $role = Role::firstWhere(['guard_name' => 'web', 'slug' => $slug])
                ?? Role::create([
                    'name' => $config['description'],
                    'guard_name' => 'web',
                    'slug' => $slug,
                ]);

            $role->update([
                'description' => $config['description'],
                'is_system' => true,
            ]);

            $role->syncPermissions($config['permissions']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
