<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\PortalResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Sends a freshly authenticated account into its primary portal.
 */
class LoginResponse implements LoginResponseContract
{
    public function __construct(private readonly PortalResolver $portals)
    {
        //
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $intended = $request->session()->pull('url.intended');

        return redirect()->to(
            $this->portals->loginDestination($user, is_string($intended) ? $intended : null),
        );
    }
}
