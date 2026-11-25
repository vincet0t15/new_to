<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * Blocks a deactivated account during the Fortify login pipeline.
 *
 * Credentials are still checked first, so the error does not reveal whether
 * the username exists. The guard session created by AttemptToAuthenticate is
 * discarded before the request can reach a portal.
 */
class RejectInactiveAccount
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_active) {
            return $next($request);
        }

        Auth::guard(config('fortify.guard'))->logout();

        throw ValidationException::withMessages([
            Fortify::username() => __('This account is inactive. Please contact your administrator.'),
        ]);
    }
}
