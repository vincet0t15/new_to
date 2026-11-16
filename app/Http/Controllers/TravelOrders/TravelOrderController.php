<?php

namespace App\Http\Controllers\TravelOrders;

use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TravelOrders\StoreTravelOrderRequest;
use App\Http\Requests\TravelOrders\UpdateTravelOrderRequest;
use App\Models\Employee;
use App\Models\Office;
use App\Models\TravelOrder;
use App\Models\User;
use App\Repositories\Contracts\TravelOrderRepository;
use App\Services\WorkflowRouteResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TravelOrderController extends Controller
{
    public function __construct(
        private readonly TravelOrderRepository $travelOrders,
        private readonly WorkflowRouteResolver $routes,
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
     * Show the form for a new travel order.
     */
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
     * Store a new (draft or submitted) travel order.
     */
    public function store(StoreTravelOrderRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $travelOrder = $this->travelOrders->create([
            'employee_id' => $data['employee_id'] ?? $user->employee?->id,
            'office_id' => $data['office_id'] ?? $user->employee?->office_id,
            'created_by' => $user->id,
            'travel_classification' => $data['travel_classification'] ?? null,
            'fund_source' => $data['fund_source'] ?? null,
            'purpose' => $data['purpose'],
            'destination' => $data['destination'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'submission_notes' => $data['submission_notes'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => ($data['status'] ?? 'draft') === 'submitted'
                ? __('Travel order submitted.')
                : __('Travel order saved as draft.'),
        ]);

        return to_route('travel-orders.edit', $travelOrder);
    }

    /**
     * Show the edit form for a travel order owned by the user.
     */
    public function edit(Request $request, TravelOrder $travelOrder): Response
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        $travelOrder->load(['office', 'employee']);

        return Inertia::render('travel-orders/edit', [
            'travelOrder' => $travelOrder,
            'options' => $this->formOptions($travelOrder->office),
            'routePreview' => $travelOrder->office === null
                ? null
                : $this->routes->preview($travelOrder->office),
        ]);
    }

    /**
     * Update a travel order owned by the user.
     */
    public function update(UpdateTravelOrderRequest $request, TravelOrder $travelOrder): RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        $data = $request->validated();

        $this->travelOrders->update($travelOrder, [
            'employee_id' => $data['employee_id'] ?? $travelOrder->employee_id,
            'office_id' => $data['office_id'] ?? $travelOrder->office_id,
            'travel_classification' => $data['travel_classification'] ?? null,
            'fund_source' => $data['fund_source'] ?? null,
            'purpose' => $data['purpose'],
            'destination' => $data['destination'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'submission_notes' => $data['submission_notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel order updated.')]);

        return back();
    }

    /**
     * Show a travel order owned by the user.
     */
    public function show(Request $request, TravelOrder $travelOrder): Response
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        $travelOrder->load(['office', 'employee', 'workflow', 'deliveries.user', 'actions.actor']);

        return Inertia::render('travel-orders/show', [
            'travelOrder' => $travelOrder,
            'routePreview' => $travelOrder->office === null
                ? null
                : $this->routes->preview($travelOrder->office),
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
