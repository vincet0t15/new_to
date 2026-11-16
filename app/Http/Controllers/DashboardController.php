<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PortalResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Keeps the historical /dashboard URL working after the portal split.
 *
 * The route name is still used by the welcome page and by Fortify, so instead
 * of deleting it we resolve it to the account's primary portal.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly PortalResolver $portals)
    {
        //
    }

    /**
     * Redirect the signed-in account to its primary portal.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return redirect()->to($this->portals->dashboardUrl($this->portals->defaultFor($user)));
    }
}
