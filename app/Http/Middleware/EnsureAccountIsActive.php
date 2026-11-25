<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out an account that was deactivated while a session was open.
 *
 * Guests and active accounts are untouched. Deactivated accounts lose the
 * session immediately and land on the login screen with an explanation.
 */
class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_active) {
            return $next($request);
        }

        Auth::guard(config('fortify.guard'))->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'status',
            __('This account has been deactivated. Please contact your administrator.'),
        );
    }
}
