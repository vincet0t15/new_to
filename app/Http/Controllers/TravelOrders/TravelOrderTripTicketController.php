<?php

namespace App\Http\Controllers\TravelOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripTickets\CancelTripTicketRequest;
use App\Http\Requests\TripTickets\CompleteTripTicketRequest;
use App\Http\Requests\TripTickets\SaveTripTicketRequest;
use App\Models\Employee;
use App\Models\FuelType;
use App\Models\Office;
use App\Models\TravelOrder;
use App\Models\TripTicket;
use App\Models\User;
use App\Services\TripTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Trip tickets prepared from a travel order's show page.
 *
 * The ticket is nested under the order it belongs to, so the route binding
 * proves the pair together. Only the creator of the order may prepare, correct,
 * complete or print its tickets.
 */
class TravelOrderTripTicketController extends Controller
{
    public function __construct(
        private readonly TripTicketService $tickets,
    ) {
        //
    }

    public function create(Request $request, TravelOrder $travelOrder): Response|RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        $travelOrder->loadMissing('transportationModes');

        if (! $travelOrder->usesGovernmentVehicle()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('A trip ticket can only be prepared when a government vehicle will be used.'),
            ]);

            return to_route('travel-orders.show', $travelOrder);
        }

        return Inertia::render('trip-tickets/create', [
            'travelOrder' => $this->orderPayload($travelOrder),
            'drivers' => $this->driverOptions(),
            'offices' => $this->officeOptions(),
            'fuelTypes' => $this->fuelTypeOptions(),
        ]);
    }

    public function store(SaveTripTicketRequest $request, TravelOrder $travelOrder): RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        $ticket = $this->tickets->create(
            $travelOrder,
            $request->validated(),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Trip ticket prepared.'),
        ]);

        return to_route('travel-orders.trip-tickets.show', [$travelOrder, $ticket]);
    }

    public function show(Request $request, TravelOrder $travelOrder, TripTicket $tripTicket): Response
    {
        $this->authorizeViewer($request->user(), $travelOrder);

        $tripTicket->load(['travelOrder.office', 'travelOrder.destinationLocation', 'driver', 'fuelSourceOffice', 'fuelTypeRecord', 'creator', 'completer']);

        return Inertia::render('trip-tickets/show', [
            'travelOrder' => $this->orderPayload($travelOrder),
            'tripTicket' => $tripTicket,
        ]);
    }

    public function edit(Request $request, TravelOrder $travelOrder, TripTicket $tripTicket): Response|RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        if (! $tripTicket->isEditable()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only an issued trip ticket can be edited.'),
            ]);

            return to_route('travel-orders.trip-tickets.show', [$travelOrder, $tripTicket]);
        }

        return Inertia::render('trip-tickets/edit', [
            'travelOrder' => $this->orderPayload($travelOrder),
            'tripTicket' => $tripTicket->load(['fuelSourceOffice:id,office_name', 'fuelTypeRecord:id,name']),
            'drivers' => $this->driverOptions(),
            'offices' => $this->officeOptions(),
            'fuelTypes' => $this->fuelTypeOptions(),
        ]);
    }

    public function update(
        SaveTripTicketRequest $request,
        TravelOrder $travelOrder,
        TripTicket $tripTicket,
    ): RedirectResponse {
        $this->authorizeOwner($request->user(), $travelOrder);

        $this->tickets->update($tripTicket, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Trip ticket updated.'),
        ]);

        return to_route('travel-orders.trip-tickets.show', [$travelOrder, $tripTicket]);
    }

    public function complete(
        CompleteTripTicketRequest $request,
        TravelOrder $travelOrder,
        TripTicket $tripTicket,
    ): RedirectResponse {
        $this->authorizeOwner($request->user(), $travelOrder);

        $this->tickets->complete($tripTicket, $request->validated(), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Trip ticket completed.'),
        ]);

        return to_route('travel-orders.trip-tickets.show', [$travelOrder, $tripTicket]);
    }

    public function cancel(
        CancelTripTicketRequest $request,
        TravelOrder $travelOrder,
        TripTicket $tripTicket,
    ): RedirectResponse {
        $this->authorizeOwner($request->user(), $travelOrder);

        $this->tickets->cancel($tripTicket, $request->validated('cancel_reason'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Trip ticket cancelled.'),
        ]);

        return to_route('travel-orders.trip-tickets.show', [$travelOrder, $tripTicket]);
    }

    public function destroy(Request $request, TravelOrder $travelOrder, TripTicket $tripTicket): RedirectResponse
    {
        $this->authorizeOwner($request->user(), $travelOrder);

        if (! $tripTicket->isCancelled()) {
            throw ValidationException::withMessages([
                'status' => 'Cancel the trip ticket before deleting it.',
            ]);
        }

        $tripTicket->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Trip ticket deleted.'),
        ]);

        return to_route('travel-orders.show', $travelOrder);
    }

    public function print(Request $request, TravelOrder $travelOrder, TripTicket $tripTicket): Response
    {
        $this->authorizeViewer($request->user(), $travelOrder);

        $tripTicket->load(['driver', 'creator']);

        return Inertia::render('trip-tickets/print', [
            'travelOrder' => $this->orderPayload($travelOrder),
            'tripTicket' => $tripTicket,
            'letterhead' => config('travel-orders.letterhead'),
        ]);
    }

    /**
     * The order fields the ticket screens read.
     *
     * @return array<string, mixed>
     */
    private function orderPayload(TravelOrder $travelOrder): array
    {
        $travelOrder->loadMissing([
            'office',
            'employee',
            'origin',
            'destinationLocation',
            'itineraries.fromLocation',
            'itineraries.toLocation',
        ]);

        return [
            'id' => $travelOrder->id,
            'control_no' => $travelOrder->control_no,
            'to_number' => $travelOrder->to_number,
            'purpose' => $travelOrder->purpose,
            'date_from' => $travelOrder->date_from?->toDateString(),
            'date_to' => $travelOrder->date_to?->toDateString(),
            'status' => $travelOrder->status->value,
            'office' => $travelOrder->office?->only(['id', 'office_name']),
            'employee' => $travelOrder->employee?->only(['id', 'full_name']),
            'origin' => $travelOrder->origin?->only(['id', 'name']),
            'destination' => $travelOrder->destinationLocation?->only(['id', 'name']),
            'itineraries' => $travelOrder->itineraries->map(fn ($entry): array => [
                'id' => $entry->id,
                'date_from' => $entry->date_from->toDateString(),
                'date_to' => $entry->date_to->toDateString(),
                'from_location' => $entry->fromLocation?->only(['id', 'name']),
                'to_location' => $entry->toLocation?->only(['id', 'name']),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<int, array{id: int, full_name: string}>
     */
    private function driverOptions(): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, office_name: string}>
     */
    private function officeOptions(): array
    {
        return Office::query()
            ->orderBy('office_name')
            ->get(['id', 'office_name'])
            ->toArray();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function fuelTypeOptions(): array
    {
        return FuelType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    private function authorizeOwner(?User $user, TravelOrder $travelOrder): void
    {
        abort_unless($user instanceof User && $travelOrder->created_by === $user->id, 403);
    }

    private function authorizeViewer(?User $user, TravelOrder $travelOrder): void
    {
        abort_unless($user instanceof User, 403);

        if ($travelOrder->created_by === $user->id) {
            return;
        }

        abort_unless(
            $user->can('travel-orders.view-all')
            || $travelOrder->deliveries()->where('user_id', $user->id)->exists()
            || $travelOrder->actions()->where('actor_user_id', $user->id)->exists(),
            403,
        );
    }
}
