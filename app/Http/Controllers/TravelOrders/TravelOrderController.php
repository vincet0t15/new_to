<?php

namespace App\Http\Controllers\TravelOrders;

use App\Enums\TravelOrderStatus;
use App\Exceptions\TravelOrderRoutingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TravelOrders\ResubmitTravelOrderRequest;
use App\Http\Requests\TravelOrders\StoreTravelOrderRequest;
use App\Http\Requests\TravelOrders\UpdateTravelOrderRequest;
use App\Models\Employee;
use App\Models\ExpenseType;
use App\Models\FuelType;
use App\Models\Location;
use App\Models\Office;
use App\Models\TransportationMode;
use App\Models\TravelClassification;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\Contracts\TravelOrderRepository;
use App\Services\ItinerarySignatoryResolver;
use App\Services\SignatoryResolver;
use App\Services\TravelOrderApprovalService;
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
        private readonly ItinerarySignatoryResolver $itinerarySignatories,
        private readonly TravelOrderApprovalService $approvals,
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
        // An unrecognised value simply leaves the register unfiltered rather
        // than turning a typo into an empty page.
        $status = TravelOrderStatus::tryFrom($request->string('status')->toString());

        return Inertia::render('travel-orders/index', [
            'travelOrders' => $this->travelOrders->paginateForCreator(
                $user,
                $search !== '' ? $search : null,
                status: $status,
            ),
            'filters' => [
                'search' => $search,
                'status' => $status !== null ? $status->value : '',
            ],
            'statuses' => array_map(
                fn (TravelOrderStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                TravelOrderStatus::selectable(),
            ),
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

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        $office = $employee?->office;

        if ($employee === null || $office === null) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Your account must be linked to an employee record with an office before filing a travel order.'),
            ]);

            return to_route('travel-orders.index');
        }

        return Inertia::render('travel-orders/create', [
            'options' => $this->formOptions(),
            'initial' => [
                'travel_classification_id' => '',
                'origin_id' => '',
                'destination_id' => '',
                'official_station_id' => '',
                'purpose' => '',
                'date_from' => '',
                'date_to' => '',
                'submission_notes' => '',
                'other_expenses' => '',
                'expense_type_ids' => [],
                'transportation_mode_ids' => [],
            ],
            'routePreview' => $this->routes->preview($office),
            'filer' => [
                'name' => $employee->full_name,
                'office' => $office->office_name,
            ],
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

        $employee = $user->employee;

        if ($employee === null) {
            return back()->withErrors([
                'employee' => __('Your account must be linked to an employee record with an office before filing a travel order.'),
            ]);
        }

        try {
            $travelOrder = DB::transaction(function () use ($data, $user, $submit, $employee): TravelOrder {
                $travelOrder = $this->travelOrders->create([
                    ...$this->contentPayload($data),
                    'employee_id' => $employee->id,
                    'office_id' => $employee->office_id,
                    'created_by' => $user->id,
                    'status' => TravelOrderStatus::Draft,
                ]);

                $this->syncSelections($travelOrder, $data);

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

        // Whether the order was filed or kept as a draft, the next thing the
        // filer needs is the view where the itinerary and trip ticket live.
        return to_route('travel-orders.show', $travelOrder);
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

        $travelOrder->load([
            'office',
            'employee',
            'returnedBy',
            'earmarkedBy',
            'fundSourceAssignedBy',
            'recordedBy',
            'travelClassification',
            'fundSource',
            'origin',
            'destinationLocation',
            'officialStation',
            'expenseTypes',
            'transportationModes',
            'revisions.returnedBy',
            'revisions.revisedBy',
        ]);

        return Inertia::render('travel-orders/edit', [
            'travelOrder' => $travelOrder,
            'options' => $this->formOptions(),
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

        DB::transaction(function () use ($data, $travelOrder): void {
            $this->travelOrders->update($travelOrder, $this->contentPayload($data));
            $this->syncSelections($travelOrder, $data);
        });

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
                $this->travelOrders->update($travelOrder, $this->contentPayload($data));
                $this->syncSelections($travelOrder, $data);

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
            'revisions.returnedBy',
            'revisions.revisedBy',
            'travelClassification',
            'fundSource',
            'origin',
            'destinationLocation',
            'officialStation',
            'expenseTypes',
            'transportationModes',
            'itineraries.fromLocation',
            'itineraries.toLocation',
            'itineraries.transportationMode',
            'tripTickets.driver',
            'tripTickets.fuelTypeRecord',
            'tripTickets.fuelSourceOffice',
        ]);

        $editable = $travelOrder->status === TravelOrderStatus::Draft
            || $travelOrder->status === TravelOrderStatus::Returned;

        $step = $this->approvals->currentStep($travelOrder);

        return Inertia::render('travel-orders/show', [
            'travelOrder' => $travelOrder,
            'route' => $this->presenter->route($travelOrder),
            'timeline' => $this->presenter->timeline($travelOrder, includeInternal: false),
            'revisions' => $this->presenter->revisions($travelOrder),
            'currentStep' => $step === null ? null : [
                'label' => $step->displayLabel(),
                'office' => $step->office->office_name
                    ?? $travelOrder->office?->office_name,
                'is_signatory' => $step->stepType->is_signatory,
            ],
            'options' => [
                'locations' => Location::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'is_active'])
                    ->toArray(),
                'transportationModes' => TransportationMode::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'is_active', 'is_government'])
                    ->toArray(),
                'vehicles' => Vehicle::query()
                    ->active()
                    ->orderBy('plate_no')
                    ->get(['id', 'plate_no', 'vehicle_type', 'model', 'capacity', 'driver_id'])
                    ->toArray(),
                'drivers' => Employee::query()
                    ->where('is_active', true)
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix'])
                    ->map(fn (Employee $employee): array => [
                        'id' => $employee->id,
                        'full_name' => $employee->full_name,
                    ])
                    ->values()
                    ->all(),
                'offices' => Office::query()
                    ->orderBy('office_name')
                    ->get(['id', 'office_name'])
                    ->toArray(),
                'fuelTypes' => FuelType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])->toArray(),
            ],
            'can' => [
                'submit' => $travelOrder->status === TravelOrderStatus::Draft,
                'edit' => $editable,
                'resubmit' => $travelOrder->status === TravelOrderStatus::Returned,
                'delete' => $travelOrder->status === TravelOrderStatus::Draft,
                'manageItinerary' => $editable,
                'createTripTicket' => $editable && $travelOrder->usesGovernmentVehicle(),
            ],
            'urls' => [
                'index' => route('travel-orders.index'),
                'edit' => route('travel-orders.edit', $travelOrder),
                'submit' => route('travel-orders.submit', $travelOrder),
                'resubmit' => route('travel-orders.resubmit', $travelOrder),
                'destroy' => route('travel-orders.destroy', $travelOrder),
                'tripTicketCreate' => route('travel-orders.trip-tickets.create', $travelOrder),
                'printOrder' => route('travel-orders.print.order', $travelOrder),
                'printItinerary' => route('travel-orders.print.itinerary', $travelOrder),
            ],
        ]);
    }

    /**
     * Print the travel order form itself.
     *
     * The signature lines were frozen when the order was submitted, so a
     * designation changing hands later does not restyle a filed copy. A draft
     * has never been routed, so its lines resolve live.
     */
    public function printOrder(Request $request, TravelOrder $travelOrder): Response
    {
        $this->authorizePrintViewer($request->user(), $travelOrder);

        $travelOrder->load($this->printRelations());

        return Inertia::render('travel-orders/print-order', [
            'travelOrder' => $travelOrder,
            'letterhead' => $this->letterhead(),
            'signatories' => $travelOrder->signatories
                ?? app(SignatoryResolver::class)->for($travelOrder),
        ]);
    }

    /**
     * Print the Actual Itinerary of Travel (Appendix A).
     */
    public function printItinerary(Request $request, TravelOrder $travelOrder): Response
    {
        $this->authorizePrintViewer($request->user(), $travelOrder);

        $travelOrder->load($this->printRelations());

        return Inertia::render('travel-orders/print-itinerary', [
            'travelOrder' => $travelOrder,
            'letterhead' => $this->letterhead(),
            'itinerarySignatories' => $this->itinerarySignatories($travelOrder),
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
     * Inactive lookups are still sent so an order that already references one
     * can render its saved value while editing. The form hides them unless
     * they are the selected value.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
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
            'classifications' => TravelClassification::query()
                ->orderBy('name')
                ->get(['id', 'name', 'is_active'])
                ->map(fn (TravelClassification $classification): array => [
                    'id' => $classification->id,
                    'name' => $classification->name,
                    'is_active' => $classification->is_active,
                ])
                ->values()
                ->all(),
            'fundSources' => [],
            'locations' => Location::query()
                ->orderBy('name')
                ->get(['id', 'name', 'is_active'])
                ->map(fn (Location $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'is_active' => $location->is_active,
                ])
                ->values()
                ->all(),
            'expenseTypes' => ExpenseType::query()
                ->orderBy('name')
                ->get(['id', 'name', 'is_active'])
                ->map(fn (ExpenseType $expenseType): array => [
                    'id' => $expenseType->id,
                    'name' => $expenseType->name,
                    'is_active' => $expenseType->is_active,
                ])
                ->values()
                ->all(),
            'transportationModes' => TransportationMode::query()
                ->orderBy('name')
                ->get(['id', 'name', 'is_active'])
                ->map(fn (TransportationMode $mode): array => [
                    'id' => $mode->id,
                    'name' => $mode->name,
                    'is_active' => $mode->is_active,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * The header columns, with the legacy text mirrors kept in step.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function contentPayload(array $data): array
    {
        $classificationId = $this->intOrNull($data['travel_classification_id'] ?? null);
        $destinationId = $this->intOrNull($data['destination_id'] ?? null);

        $classification = $classificationId === null
            ? null
            : TravelClassification::query()->find($classificationId);
        $destination = $destinationId === null
            ? null
            : Location::query()->find($destinationId);

        return [
            'travel_classification_id' => $classification?->id,
            'travel_classification' => $classification?->name,
            'origin_id' => $this->intOrNull($data['origin_id'] ?? null),
            'destination_id' => $destination?->id,
            'destination' => $destination->name ?? null,
            'official_station_id' => $this->intOrNull($data['official_station_id'] ?? null),
            'other_expenses' => filled($data['other_expenses'] ?? null)
                ? trim((string) $data['other_expenses'])
                : null,
            'purpose' => $data['purpose'],
            'submission_notes' => $data['submission_notes'] ?? null,
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
        ];
    }

    /**
     * Replace the header selections that live in pivot tables.
     *
     * A request that omits an array leaves the existing selection alone, so
     * older clients and partial updates cannot silently clear the form.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncSelections(TravelOrder $travelOrder, array $data): void
    {
        if (array_key_exists('expense_type_ids', $data)) {
            $travelOrder->expenseTypes()->sync($this->intList($data['expense_type_ids']));
        }

        if (array_key_exists('transportation_mode_ids', $data)) {
            $travelOrder->transportationModes()->sync($this->intList($data['transportation_mode_ids']));
        }
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array<int, int>
     */
    private function intList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $entry) {
            if (is_numeric($entry)) {
                $ids[] = (int) $entry;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * The relations a printed document reads.
     *
     * @return list<string>
     */
    private function printRelations(): array
    {
        return [
            'employee.position',
            'employee.office',
            'office',
            'creator',
            'officialStation',
            'destinationLocation',
            'expenseTypes',
            'transportationModes',
            'itineraries.fromLocation',
            'itineraries.toLocation',
            'itineraries.transportationMode',
        ];
    }

    /**
     * The LGU letterhead configured for printed documents.
     *
     * @return array{republic: string, province: string, municipality: string, office_name: string, official_station: string, station_line: string|null}
     */
    private function letterhead(): array
    {
        /** @var array{republic: string, province: string, municipality: string, office_name: string, official_station: string, station_line: string|null} $letterhead */
        $letterhead = config('travel-orders.letterhead');

        return $letterhead;
    }

    /**
     * The two itinerary (Appendix A) signature lines for a travel order.
     *
     * The office the order is filed from names an itinerary signatory group;
     * whichever of its two lines names an employee prints that name, with the
     * group's own printed title, then the employee's position, then the
     * standard role title of the line. A line the group leaves alone prints the
     * standard role title over an empty name.
     *
     * @return array{certified: array{name: string|null, title: string}, approved: array{name: string|null, title: string}}
     */
    private function itinerarySignatories(TravelOrder $travelOrder): array
    {
        return $this->itinerarySignatories->for($travelOrder);
    }

    /**
     * Allow the owner, or somebody the order was delivered to, to print it.
     */
    private function authorizePrintViewer(?User $user, TravelOrder $travelOrder): void
    {
        abort_unless($user instanceof User, 401);

        abort_unless(
            $travelOrder->isOwnedBy($user)
                || $travelOrder->deliveries()->where('user_id', $user->id)->exists(),
            403,
        );
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
