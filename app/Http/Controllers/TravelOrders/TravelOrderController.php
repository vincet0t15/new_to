<?php

namespace App\Http\Controllers\TravelOrders;

use App\Enums\TravelOrderStatus;
use App\Exceptions\TravelOrderRoutingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TravelOrders\ResubmitTravelOrderRequest;
use App\Http\Requests\TravelOrders\StoreTravelOrderRequest;
use App\Http\Requests\TravelOrders\UpdateTravelOrderRequest;
use App\Models\Employee;
use App\Models\Office;
use App\Models\TravelOrder;
use App\Models\User;
use App\Repositories\Contracts\TravelOrderRepository;
use App\Services\TravelOrderResubmissionService;
use App\Services\TravelOrderRoutePresenter;
use App\Services\TravelOrderSubmissionService;
use App\Services\WorkflowRouteResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TravelOrderController extends Controller
{
    public function __construct(
        private readonly TravelOrderRepository $travelOrders,
        private readonly WorkflowRouteResolver $routes,
        private readonly TravelOrderSubmissionService $submissions,
        private readonly TravelOrderRoutePresenter $presenter,
        private readonly TravelOrderResubmissionService $resubmissions,
    ) {
        //
    }

    /**
     * List the travel orders created by the signed-in user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('search')->toString();

        return Inertia::render('travel-orders/index', [
            'travelOrders' => $this->travelOrders->paginateForCreator(
                $user,
                $search !== '' ? $search : null,
            ),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * List the travel orders returned to the signed-in user for revision.
     */
    public function returned(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('search')->toString();

        return Inertia::render('travel-orders/returned', [
            'travelOrders' => $this->travelOrders->paginateReturnedForCreator(
                $user,
                $search !== '' ? $search : null,
            ),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $employee = $user->employee;
        $office = $employee?->office;

        return Inertia::render('travel-orders/create', [
            'options' => $this->formOptions($office),
            'initial' => [
                'employee_id' => $employee?->id,
                'office_id' => $office?->id,
                'travel_classification' => '',
                'fund_source' => '',
                'purpose' => '',
                'destination' => '',
                'date_from' => '',
                'date_to' => '',
                'submission_notes' => '',
            ],
            'routePreview' => $office === null ? null : $this->routes->preview($office),
        ]);
    }

    /**
     * Store a new travel order and, when asked, file it immediately.
     */
    public function store(StoreTravelOrderRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $submit = ($data['status'] ?? 'draft') === 'submitted';

        if ($submit) {
            abort_unless($user->can('travel-orders.send'), 403);
        }

        try {
            $travelOrder = DB::transaction(function () use ($data, $user, $submit): TravelOrder {
                $travelOrder = $this->travelOrders->create([
                    'employee_id' => $data['employee_id'] ?? $user->employee?->id,
                    'office_id' => $data['office_id'] ?? $user->employee?->office_id,
                    'created_by' => $user->id,
                    'travel_classification' => $data['travel_classification'] ?? null,
                    'fund_source' => $data['fund_source'] ?? null,
                    'purpose' => $data['purpose'],
                    'submission_notes' => $data['submission_notes'] ?? null,
                    'destination' => $data['destination'],
                    'date_from' => $data['date_from'],
                    'date_to' => $data['date_to'],
                    'status' => TravelOrderStatus::Draft,
                ]);

                if ($submit) {
                    $this->submissions->submit($travelOrder, $user);
                }

                return $travelOrder->refresh();
            });
        } catch (TravelOrderRoutingException $exception) {
            return back()->withErrors(['routing' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $travelOrder->status === TravelOrderStatus::Submitted
                ? __('Travel order submitted.')
                : __('Travel order saved as draft.'),
        ]);

        return $travelOrder->status === TravelOrderStatus::Submitted
            ? to_route('travel-orders.show', $travelOrder)
            : to_route('travel-orders.edit', $travelOrder);
    }

    /**
     * Show the edit form for a draft owned by the user.
     */
    public function edit(Request $request, TravelOrder $travelOrder): Response|RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        if (! in_array($travelOrder->status, [TravelOrderStatus::Draft, TravelOrderStatus::Returned], true)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only draft or returned travel orders can be edited.'),
            ]);

            return to_route('travel-orders.show', $travelOrder);
        }

        $travelOrder->load(['office', 'employee', 'returnedBy']);

        return Inertia::render('travel-orders/edit', [
            'travelOrder' => $travelOrder,
            'options' => $this->formOptions($travelOrder->office),
            'canSubmit' => $travelOrder->status === TravelOrderStatus::Draft
                && $travelOrder->created_by === $request->user()?->id,
            'canResubmit' => $travelOrder->status === TravelOrderStatus::Returned
                && $travelOrder->created_by === $request->user()?->id,
            'submitUrl' => route('travel-orders.submit', $travelOrder),
            'resubmitUrl' => route('travel-orders.resubmit', $travelOrder),
            'revisions' => $this->presenter->revisions($travelOrder),
        ]);
    }

    /**
     * Update a draft travel order owned by the user.
     */
    public function update(UpdateTravelOrderRequest $request, TravelOrder $travelOrder): RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        if (! in_array($travelOrder->status, [TravelOrderStatus::Draft, TravelOrderStatus::Returned], true)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only draft or returned travel orders can be edited.'),
            ]);

            return back();
        }

        $data = $request->validated();

        $this->travelOrders->update($travelOrder, [
            'employee_id' => $data['employee_id'] ?? $travelOrder->employee_id,
            'office_id' => $data['office_id'] ?? $travelOrder->office_id,
            'travel_classification' => $data['travel_classification'] ?? null,
            'fund_source' => $data['fund_source'] ?? null,
            'purpose' => $data['purpose'],
            'submission_notes' => $data['submission_notes'] ?? null,
            'destination' => $data['destination'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel order updated.')]);

        return back();
    }

    /**
     * File a draft into its approval chain.
     */
    public function submit(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        abort_unless($request->user()?->can('travel-orders.send'), 403);

        try {
            $this->submissions->submit($travelOrder, $request->user());
        } catch (TravelOrderRoutingException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['routing' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Travel order submitted.'),
        ]);

        return to_route('travel-orders.show', $travelOrder);
    }

    /**
     * File a returned travel order back into its approval chain.
     */
    public function resubmit(ResubmitTravelOrderRequest $request, TravelOrder $travelOrder): RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        abort_unless($request->user()?->can('travel-orders.resubmit'), 403);

        $data = $request->validated();
        $remarks = trim((string) ($data['revision_remarks'] ?? ''));

        try {
            DB::transaction(function () use ($data, $travelOrder, $request, $remarks): void {
                $this->travelOrders->update($travelOrder, [
                    'employee_id' => $data['employee_id'] ?? $travelOrder->employee_id,
                    'office_id' => $data['office_id'] ?? $travelOrder->office_id,
                    'travel_classification' => $data['travel_classification'] ?? null,
                    'fund_source' => $data['fund_source'] ?? null,
                    'purpose' => $data['purpose'],
                    'submission_notes' => $data['submission_notes'] ?? null,
                    'destination' => $data['destination'],
                    'date_from' => $data['date_from'],
                    'date_to' => $data['date_to'],
                ]);

                $this->resubmissions->resubmit(
                    $travelOrder,
                    $request->user(),
                    $remarks,
                );
            });
        } catch (TravelOrderRoutingException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['routing' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Travel order resubmitted.'),
        ]);

        return to_route('travel-orders.show', $travelOrder);
    }

    public function show(Request $request, TravelOrder $travelOrder): Response
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        $travelOrder->load([
            'office',
            'employee',
            'creator',
            'returnedBy',
            'workflow',
            'deliveries.user',
            'actions.actor',
        ]);

        return Inertia::render('travel-orders/show', [
            'travelOrder' => $travelOrder,
            'route' => $this->presenter->route($travelOrder),
            'timeline' => $this->presenter->timeline($travelOrder),
            'revisions' => $this->presenter->revisions($travelOrder),
            'can' => [
                'submit' => $travelOrder->status === TravelOrderStatus::Draft,
                'edit' => $travelOrder->status === TravelOrderStatus::Draft
                    || $travelOrder->status === TravelOrderStatus::Returned,
                'resubmit' => $travelOrder->status === TravelOrderStatus::Returned,
                'delete' => $travelOrder->status === TravelOrderStatus::Draft,
            ],
            'urls' => [
                'index' => route('travel-orders.index'),
                'edit' => route('travel-orders.edit', $travelOrder),
                'submit' => route('travel-orders.submit', $travelOrder),
                'resubmit' => route('travel-orders.resubmit', $travelOrder),
                'destroy' => route('travel-orders.destroy', $travelOrder),
            ],
        ]);
    }

    /**
     * Remove a draft travel order owned by the user.
     */
    public function destroy(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        if ($travelOrder->status !== TravelOrderStatus::Draft) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Only draft travel orders can be deleted.')]);

            return back();
        }

        $travelOrder->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel order deleted.')]);

        return to_route('travel-orders.index');
    }

    /**
     * The shared option lists for the form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(?Office $office): array
    {
        return [
            'offices' => Office::query()->orderBy('office_name')->get(['id', 'office_name']),
            'employees' => Employee::query()
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'office_id'])
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'office_id' => $employee->office_id,
                ])
                ->values()
                ->all(),
            'classifications' => [
                'Local Travel',
                'Foreign Travel',
                'Seminar/Training',
                'Official Business',
            ],
            'fundSources' => [
                'MOOE',
                'Capital Outlay',
                'Personal Funds',
                'Other',
            ],
        ];
    }

    /**
     * Ensure the signed-in user owns the travel order.
     */
    private function authorizeOwner(?User $user, TravelOrder $travelOrder): void
    {
        abort_unless(
            $user instanceof User && $travelOrder->created_by === $user->id,
            403,
        );
    }
}
