<?php

namespace App\Http\Controllers\Portals;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\TravelOrderRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The two portal landings that show real work data.
 *
 * The approvals portal has no dashboard of its own: its inbox is the landing
 * page, so its counts travel with the inbox response instead.
 */
final class PortalDashboardController extends Controller
{
    public function __construct(private readonly TravelOrderRepository $orders)
    {
        //
    }

    /**
     * The personal workspace: only orders this account filed.
     */
    public function employee(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return Inertia::render('portals/employee/dashboard', [
            'stats' => $this->orders->countStatusesForCreator($user),
        ]);
    }

    /**
     * The department head processing workspace.
     */
    public function departmentHead(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return Inertia::render('portals/department-head/dashboard', [
            'stats' => $this->orders->processingCounts($user),
        ]);
    }
}
