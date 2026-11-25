<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Limits public registration attempts by IP address.
 *
 * Fortify does not expose a registration limiter in its route definitions,
 * so this runs in the web group and only touches the registration endpoint.
 */
class ThrottleRegistration
{
    public function __construct(private readonly RateLimiter $limiter) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('register.store')) {
            return $next($request);
        }

        $attempts = (int) config('fortify.registration_rate_limit.attempts', 5);
        $decaySeconds = (int) config('fortify.registration_rate_limit.decay_seconds', 60);
        $key = 'registration:'.$request->ip();

        if ($this->limiter->tooManyAttempts($key, $attempts)) {
            throw new TooManyRequestsHttpException(
                $this->limiter->availableIn($key),
                __('Too many registration attempts. Please try again later.'),
            );
        }

        $this->limiter->hit($key, $decaySeconds);

        return $next($request);
    }
}
