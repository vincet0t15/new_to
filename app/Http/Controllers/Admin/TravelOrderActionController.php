<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TravelOrderRoutingException;
use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use App\Models\User;
use App\Services\TravelOrderApprovalService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin-only actions on travel orders.
 *
 * The register is otherwise read-only; this controller hosts the rescue
 * actions (reassign a stranded document) that only an administrator may run.
 */
class TravelOrderActionController extends Controller
{
    public function __construct(
        private readonly TravelOrderApprovalService $approvals,
    ) {
        //
    }

    public function reassign(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'to_user_id.required' => __('Please choose who should receive the travel order.'),
        ]);

        $target = User::query()->findOrFail((int) $validated['to_user_id']);

        return $this->run(
            fn () => $this->approvals->reassignCurrentStep(
                $travelOrder,
                $actor,
                $target,
                $validated['remarks'] ?? null,
            ),
            __('Travel order reassigned.'),
        );
    }

    /**
     * Run an action and report a routing refusal on the same screen.
     */
    private function run(Closure $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (TravelOrderRoutingException $exception) {
            return back()->withErrors(['action' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
