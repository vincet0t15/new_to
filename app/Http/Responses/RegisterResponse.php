<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\PortalResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * Sends a newly registered account into its primary portal.
 *
 * Registration creates an employee and its basic login, but grants no role,
 * so this always resolves to the baseline employee portal until an
 * administrator grants another role.
 */
class RegisterResponse implements RegisterResponseContract
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
            return response()->json('', 201);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        return redirect()->to($this->portals->dashboardUrl($this->portals->defaultFor($user)));
    }
}
