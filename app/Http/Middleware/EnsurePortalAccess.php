<?php

namespace App\Http\Middleware;

use App\Enums\Portal;
use App\Models\User;
use App\Services\PortalResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks portal routes the signed-in account may not enter.
 *
 * Hiding a navigation item is not authorization. Every portal route group
 * declares the portal it belongs to, and this middleware fails closed when the
 * account holds none of the granting roles.
 */
class EnsurePortalAccess
{
    public function __construct(private readonly PortalResolver $portals)
    {
        //
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$portals): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        foreach ($portals as $key) {
            $portal = Portal::tryFrom($key);

            if ($portal !== null && $this->portals->canEnter($portal, $user)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have access to this portal.');
    }
}
