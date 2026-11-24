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
     * Rescue several stranded orders in one pass.
     *
     * Each order is reassignCurrentStep()'d on its own, so a document that can
     * no longer be routed is reported without undoing the successful ones.
     */
    public function reassignBulk(Request $request): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 401);

        $validated = $request->validate([
            'travel_order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'travel_order_ids.*' => ['integer', 'exists:travel_orders,id'],
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'travel_order_ids.required' => __('Choose at least one travel order.'),
            'to_user_id.required' => __('Please choose who should receive the travel orders.'),
        ]);

        $target = User::query()->findOrFail((int) $validated['to_user_id']);

        $orders = TravelOrder::query()
            ->whereIn('id', $validated['travel_order_ids'])
            ->get();

        $result = $this->approvals->reassignMany(
            $orders,
            $actor,
            $target,
            $validated['remarks'] ?? null,
        );

        $reassigned = count($result['reassigned']);
        $failed = count($result['failed']);

        if ($failed > 0) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __(':done reassigned, :failed could not be reassigned.', [
                    'done' => $reassigned,
                    'failed' => $failed,
                ]),
            ]);

            return back()->withErrors([
                'action' => collect($result['failed'])
                    ->map(fn (array $failure): string => "#{$failure['id']}: {$failure['message']}")
                    ->join(' '),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':count travel orders reassigned.', ['count' => $reassigned]),
        ]);

        return back();
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
