<?php

namespace App\Http\Controllers\Accounts;

use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\StoreUserAccountRequest;
use App\Http\Requests\Accounts\UpdateUserRolesRequest;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserAccountController extends Controller
{
    /**
     * List the login accounts and the roles they hold.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $currentUserId = $request->user()?->getKey();

        $accounts = User::query()
            ->with([
                'roles:id,name,slug,is_system',
                'employee:id,first_name,middle_name,last_name,suffix,office_id',
                'employee.office:id,office_name',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'username' => $account->username,
                'email' => $account->email,
                'employee' => $account->employee === null ? null : [
                    'id' => $account->employee->id,
                    'full_name' => $account->employee->full_name,
                    'office_name' => $account->employee->office->office_name,
                ],
                'roles' => $account->roles
                    ->map(fn (Role $role): array => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'is_system' => $role->is_system,
                    ])
                    ->values()
                    ->all(),
                'created_at' => $account->created_at?->toDateString(),
                'is_self' => $account->id === $currentUserId,
            ]);

        return Inertia::render('accounts/index', [
            'accounts' => $accounts,
            'roles' => $this->roleOptions(),
            'employees' => $this->employeeOptions(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Create a login account and attach its roles.
     */
    public function store(StoreUserAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(403);
        }

        $roles = $this->rolesFromIds($validated);

        $this->assertMayTouchAdministratorRoles($actor, $roles);

        $account = User::create([
            'name' => $validated['name'],
            'username' => Str::lower($validated['username']),
            'email' => $validated['email'] ?? null,
            'password' => $validated['password'],
        ]);

        $account->syncRoles($roles);

        $employeeId = $validated['employee_id'] ?? null;

        if (is_numeric($employeeId)) {
            $employee = Employee::query()->find((int) $employeeId);

            if ($employee instanceof Employee) {
                $account->employee()->associate($employee);
                $account->save();
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account created.')]);

        return back();
    }

    /**
     * Replace the roles an account holds.
     */
    public function updateRoles(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(403);
        }

        // Changing your own roles is how an administrator locks themselves out
        // of the screen that would fix it, so it is always refused.
        if ($user->is($actor)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('You cannot change your own roles. Ask another administrator.'),
            ]);

            return back();
        }

        $roles = $this->rolesFromIds($request->validated());

        $this->assertMayTouchAdministratorRoles($actor, $roles, $user->roles);

        // Losing the last account that can administer the system would leave it
        // with no way back in.
        if ($this->wouldRemoveLastAdministrator($user, $roles)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This is the last administrator account. Give another account administrator access first.'),
            ]);

            return back();
        }

        $user->syncRoles($roles);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account roles updated.')]);

        return back();
    }

    /**
     * Every role the account may be given, with what it grants.
     *
     * @return array<int, array{id: int, name: string, slug: string, description: string|null, is_system: bool, permissions_count: int, requires_admin_permission: bool}>
     */
    private function roleOptions(): array
    {
        return Role::query()
            ->withCount('permissions')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'permissions_count' => (int) $role->permissions_count,
                'requires_admin_permission' => in_array($role->slug, $this->administratorSlugs(), true),
            ])
            ->values()
            ->all();
    }

    /**
     * Active employees who do not have a login yet.
     *
     * @return array<int, array{id: int, full_name: string, office_name: string}>
     */
    private function employeeOptions(): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->whereDoesntHave('user')
            ->with('office:id,office_name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'office_name' => $employee->office->office_name,
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve the submitted role ids to role models.
     *
     * @param  array<string, mixed>  $validated
     * @return Collection<int, Role>
     */
    private function rolesFromIds(array $validated): Collection
    {
        $ids = $validated['roles'] ?? [];
        $ids = is_array($ids) ? array_map(intval(...), $ids) : [];

        return Role::query()->whereIn('id', $ids)->get();
    }

    /**
     * Refuse changes to administrator roles by accounts that cannot manage them.
     *
     * @param  Collection<int, Role>  $nextRoles
     * @param  Collection<int, Role>|null  $currentRoles
     */
    private function assertMayTouchAdministratorRoles(
        User $actor,
        Collection $nextRoles,
        ?Collection $currentRoles = null,
    ): void {
        $administratorSlugs = $this->administratorSlugs();

        $next = $nextRoles
            ->whereIn('slug', $administratorSlugs)
            ->pluck('slug')
            ->sort()
            ->values()
            ->all();

        $current = $currentRoles === null
            ? []
            : $currentRoles
                ->whereIn('slug', $administratorSlugs)
                ->pluck('slug')
                ->sort()
                ->values()
                ->all();

        if ($next === $current || $actor->can('accounts.manage-admins')) {
            return;
        }

        throw ValidationException::withMessages([
            'roles' => __('Only a Super Admin may grant or revoke an administrator role.'),
        ]);
    }

    /**
     * Whether the change would leave the system without an administrator.
     *
     * @param  Collection<int, Role>  $nextRoles
     */
    private function wouldRemoveLastAdministrator(User $user, Collection $nextRoles): bool
    {
        $administratorSlugs = $this->administratorSlugs();

        if (! $user->roles->whereIn('slug', $administratorSlugs)->isNotEmpty()) {
            return false;
        }

        if ($nextRoles->whereIn('slug', $administratorSlugs)->isNotEmpty()) {
            return false;
        }

        return ! User::query()
            ->whereKeyNot($user->getKey())
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', $administratorSlugs))
            ->exists();
    }

    /**
     * The role slugs that carry administrative access.
     *
     * @return array<int, string>
     */
    private function administratorSlugs(): array
    {
        return [
            SystemRole::Admin->value,
            SystemRole::SuperAdmin->value,
        ];
    }
}
