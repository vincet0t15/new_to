<?php

namespace App\Http\Controllers\Approvals;

use App\Exceptions\TravelOrderRoutingException;
use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use App\Models\User;
use App\Services\TravelOrderApprovalService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * The approve / acknowledge / return / forward actions.
 *
 * Permission middleware decides which verbs an account may use. The service
 * decides whether this account actually holds the step, so an administrator
 * with the permission but no designation is still refused.
 */
class TravelOrderActionController extends Controller
{
    public function __construct(
        private readonly TravelOrderApprovalService $approvals,
    ) {
        //
    }

    public function receive(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $this->actor($request);

        return $this->run(
            fn () => $this->approvals->receive($travelOrder, $actor),
            __('Travel order received.'),
        );
    }

    public function approve(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $this->actor($request);
        $remarks = $this->remarks($request);

        return $this->run(
            fn () => $this->approvals->approve($travelOrder, $actor, $remarks, $this->internal($request)),
            __('Travel order approved.'),
        );
    }

    public function acknowledge(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $this->actor($request);
        $remarks = $this->remarks($request);

        return $this->run(
            fn () => $this->approvals->acknowledge($travelOrder, $actor, $remarks, $this->internal($request)),
            __('Receipt acknowledged.'),
        );
    }

    public function earmark(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'earmark_number' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->run(
            fn () => $this->approvals->earmark($travelOrder, $actor, $validated),
            __('Fund earmark recorded.'),
        );
    }

    public function assignFundSource(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'fund_source_id' => ['nullable', 'integer', Rule::exists('fund_sources', 'id')],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $fundSourceId = isset($validated['fund_source_id']) ? (int) $validated['fund_source_id'] : null;

        return $this->run(
            fn () => $this->approvals->assignFundSource($travelOrder, $actor, $fundSourceId, $validated['remarks'] ?? null),
            __('Fund source assigned.'),
        );
    }

    public function record(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'control_no' => ['nullable', 'string', 'max:40', Rule::unique('travel_orders', 'control_no')->ignore($travelOrder->id)],
            'to_number' => ['nullable', 'string', 'max:40', Rule::unique('travel_orders', 'to_number')->ignore($travelOrder->id)],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'distributed' => ['sometimes', 'boolean'],
        ]);

        return $this->run(
            fn () => $this->approvals->record($travelOrder, $actor, $validated),
            __('Travel order recorded.'),
        );
    }

    public function returnTo(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:2000'],
        ], [
            'remarks.required' => __('Please explain what needs to be revised.'),
        ]);

        return $this->run(
            fn () => $this->approvals->returnTo($travelOrder, $actor, $validated['remarks']),
            __('Travel order returned to its creator.'),
        );
    }

    public function forward(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'to_user_id.required' => __('Please choose who should receive the travel order.'),
        ]);

        $target = User::query()->findOrFail((int) $validated['to_user_id']);

        return $this->run(
            fn () => $this->approvals->forward(
                $travelOrder,
                $actor,
                $target,
                $validated['remarks'] ?? null,
                $this->internal($request),
            ),
            __('Travel order forwarded.'),
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

    private function actor(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function remarks(Request $request): ?string
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        return $validated['remarks'] ?? null;
    }

    /**
     * Whether the remarks should stay hidden from the filer.
     *
     * Only actions that resolve a step accept this flag. Return remarks are
     * always visible because they tell the filer what to revise.
     */
    private function internal(Request $request): bool
    {
        return $request->boolean('is_internal');
    }
}
