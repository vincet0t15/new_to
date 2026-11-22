<?php

namespace App\Services;

use App\Enums\Portal;
use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Decides which portal an account belongs to and which ones it may enter.
 *
 * A person may hold several roles, so the resolver separates the portal that
 * owns the login redirect (the most privileged role) from the full set the
 * account can switch between.
 */
class PortalResolver
{
    /**
     * Every portal the account may enter, most privileged first.
     *
     * @return array<int, Portal>
     */
    public function availableFor(User $user): array
    {
        $portals = array_values(array_filter(
            Portal::cases(),
            fn (Portal $portal): bool => $this->canEnter($portal, $user),
        ));

        usort(
            $portals,
            fn (Portal $left, Portal $right): int => $left->priority() <=> $right->priority(),
        );

        return $portals;
    }

    /**
     * Whether the account may enter the given portal.
     */
    public function canEnter(Portal $portal, User $user): bool
    {
        if ($portal->isBaseline()) {
            return true;
        }

        $grantingRoles = array_map(
            fn (SystemRole $role): string => $role->value,
            $portal->grantingRoles(),
        );

        return $user->hasAnyRoleSlug($grantingRoles);
    }

    /**
     * The portal the account lands in after signing in.
     *
     * The existing primary-role priority decides this, so the portal and the
     * work it exposes agree with the account's most privileged role.
     */
    public function defaultFor(User $user): Portal
    {
        return match ($user->primaryRoleSlug()) {
            SystemRole::SuperAdmin->value,
            SystemRole::Admin->value => Portal::Admin,
            SystemRole::Hrmo->value,
            SystemRole::Mayor->value,
            SystemRole::BudgetOfficer->value => Portal::Approvals,
            SystemRole::DepartmentHead->value => Portal::DepartmentHead,
            default => Portal::Employee,
        };
    }

    /**
     * The portal that owns the current request, if a portal owns it.
     */
    public function currentFor(Request $request): ?Portal
    {
        return $this->portalForPath($request->path());
    }

    /**
     * Resolve a URL path to the portal whose prefix it starts with.
     */
    public function portalForPath(string $path): ?Portal
    {
        $path = trim($path, '/');

        foreach (Portal::cases() as $portal) {
            if ($path === $portal->prefix() || str_starts_with($path, $portal->prefix().'/')) {
                return $portal;
            }
        }

        return null;
    }

    /**
     * Where to send the account after signing in.
     *
     * A deep link is honoured only when the account may actually enter the
     * portal it points at; otherwise the account lands on its own dashboard
     * instead of a forbidden page.
     */
    public function loginDestination(User $user, ?string $intended = null): string
    {
        if ($intended !== null && $intended !== '') {
            $path = parse_url($intended, PHP_URL_PATH);
            $portal = is_string($path) ? $this->portalForPath($path) : null;

            if ($portal !== null && $this->canEnter($portal, $user)) {
                return $intended;
            }
        }

        return $this->dashboardUrl($this->defaultFor($user));
    }

    /**
     * The URL of a portal's landing page.
     */
    public function dashboardUrl(Portal $portal): string
    {
        return route($portal->dashboardRouteName(), absolute: false);
    }

    /**
     * The portal options shared with the frontend switcher and workspace tabs.
     *
     * @return array<int, array{key: string, label: string, url: string, inboxUrl: string|null, isCurrent: bool}>
     */
    public function optionsFor(User $user, ?Portal $current = null): array
    {
        return array_map(
            function (Portal $portal) use ($current): array {
                $inboxRoute = $portal->inboxRouteName();

                return [
                    'key' => $portal->value,
                    'label' => $portal->label(),
                    'url' => $this->dashboardUrl($portal),
                    'inboxUrl' => $inboxRoute !== null
                        ? route($inboxRoute, absolute: false)
                        : null,
                    'isCurrent' => $portal === $current,
                ];
            },
            $this->availableFor($user),
        );
    }
}
