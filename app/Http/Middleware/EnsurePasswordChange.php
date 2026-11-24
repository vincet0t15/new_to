<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends accounts with a temporary seeded password to security settings.
 *
 * The flag is opt-in and defaults off, so this middleware is inert for every
 * existing account and for ordinary local/demo data. It only guards web
 * requests: guests can still sign in, and a flagged account can still reach
 * the password form and log out.
 */
class EnsurePasswordChange
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(
            'security.edit',
            'user-password.update',
            'logout',
            'verification.*',
        )) {
            return $next($request);
        }

        return redirect()->route('security.edit');
    }
}
