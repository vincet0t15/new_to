<?php

namespace App\Http\Controllers\Roles;

use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    /**
     * List the roles and the permissions each one carries.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'users_count' => (int) $role->users_count,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ]);

        return Inertia::render('roles/index', [
            'roles' => $roles,
            'permissions' => Permission::query()->orderBy('name')->pluck('name')->values()->all(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Create a custom role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'guard_name' => 'web',
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        $role->syncPermissions($this->permissionNames($validated));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return back();
    }

    /**
     * Rename a custom role and change the permissions it carries.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();
        $actor = $request->user();
        $hasPermissions = array_key_exists('permissions', $validated);

        if (! $actor instanceof User) {
            abort(403);
        }

        // The highest role is intentionally fixed: it always holds every
        // permission so an installation cannot quietly lose the ability to
        // administer itself.
        if ($role->slug === SystemRole::SuperAdmin->value && $hasPermissions) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('The Super Admin role always holds every permission.'),
            ]);

            return back();
        }

        // Changing what a built-in role may do is an administrative act, so it
        // needs the same permission as managing administrator accounts.
        if ($role->is_system && $hasPermissions && ! $actor->can('accounts.manage-admins')) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only a Super Admin may change the permissions of a built-in role.'),
            ]);

            return back();
        }

        // Built-in roles keep their name because the app and the seeders key
        // behaviour to their stable slug; only custom roles may be renamed.
        $role->update([
            'name' => $role->is_system ? $role->name : $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if ($hasPermissions) {
            $role->syncPermissions($this->permissionNames($validated));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return back();
    }

    /**
     * Remove a custom role that is not in use.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Built-in roles cannot be deleted.'),
            ]);

            return back();
        }

        if ($role->users()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Reassign this role\'s accounts before deleting it.'),
            ]);

            return back();
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return back();
    }

    /**
     * The validated permission names, normalised to a list.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, string>
     */
    private function permissionNames(array $validated): array
    {
        $permissions = $validated['permissions'] ?? [];

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_filter($permissions, 'is_string'));
    }
}
