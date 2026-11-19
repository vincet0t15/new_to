<?php

namespace App\Http\Controllers\TravelOrders;

use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TravelOrders\SaveTravelOrderItineraryRequest;
use App\Models\TransportationMode;
use App\Models\TravelOrder;
use App\Models\TravelOrderItinerary;
use App\Models\User;
use App\Services\PerDiemComputationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The itinerary legs attached to a travel order.
 *
 * A leg may be added while the order is a draft or has been returned for
 * revision, which is when its creator is allowed to change its content. The
 * automatic per diem and fare are resolved here, on the server, so the stored
 * figures do not depend on what a browser calculated.
 */
class TravelOrderItineraryController extends Controller
{
    public function __construct(
        private readonly PerDiemComputationService $perDiem,
    ) {
        //
    }

    public function store(
        SaveTravelOrderItineraryRequest $request,
        TravelOrder $travelOrder,
    ): RedirectResponse {
        $this->authorizeEditor($request->user(), $travelOrder);

        $this->persist($travelOrder, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Itinerary entry added.'),
        ]);

        return back();
    }

    public function update(
        SaveTravelOrderItineraryRequest $request,
        TravelOrder $travelOrder,
        TravelOrderItinerary $itinerary,
    ): RedirectResponse {
        $this->authorizeEditor($request->user(), $travelOrder);

        $this->persist($travelOrder, $request->validated(), $itinerary);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Itinerary entry updated.'),
        ]);

        return back();
    }

    public function destroy(
        TravelOrder $travelOrder,
        TravelOrderItinerary $itinerary,
    ): RedirectResponse {
        $this->authorizeEditor(request()->user(), $travelOrder);

        $itinerary->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Itinerary entry removed.'),
        ]);

        return back();
    }

    /**
     * Write one leg, resolving the automatic figures first.
     *
     * The order's Origin is the trip origin. Orders from before the header had
     * one fall back to the first saved leg, which keeps their per diem
     * computation behaving the way it did when the field was introduced.
     *
     * @param  array<string, mixed>  $data
     */
    private function persist(
        TravelOrder $travelOrder,
        array $data,
        ?TravelOrderItinerary $itinerary = null,
    ): TravelOrderItinerary {
        $fromLocationId = $this->intOrNull($data['from_location_id'] ?? null);
        $toLocationId = $this->intOrNull($data['to_location_id'] ?? null);
        $modeId = $this->intOrNull($data['transportation_mode_id'] ?? null);

        $originId = $this->intOrNull($travelOrder->origin_id)
            ?? $travelOrder->itineraries()->value('from_location_id')
            ?? $fromLocationId;

        $perDiem = $data['per_diem'] ?? null;

        if (($data['per_diem_is_auto'] ?? false) === true) {
            $isReturn = $originId !== null && $toLocationId === $originId;

            $perDiem = $this->perDiem->entryAmount(
                $isReturn ? $fromLocationId : $toLocationId,
                (string) $data['date_from'],
                (string) $data['date_to'],
                $data['departure_time'] ?? null,
                $data['arrival_time'] ?? null,
                $originId !== null && $fromLocationId === $originId,
                $isReturn,
            );
        }

        $fare = $data['transportation_fare'] ?? null;

        if (($data['transportation_fare_is_auto'] ?? false) === true) {
            $fare = $this->automaticFare($modeId, $toLocationId);
        }

        $attributes = [
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'departure_time' => $data['departure_time'],
            'arrival_time' => $data['arrival_time'] ?? null,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'activity' => $data['activity'],
            'reason' => $data['reason'] ?? null,
            'transportation_mode_id' => $modeId,
            'transportation_fare' => $fare,
            'transportation_fare_is_auto' => (bool) ($data['transportation_fare_is_auto'] ?? false),
            'per_diem' => $perDiem,
            'per_diem_is_auto' => (bool) ($data['per_diem_is_auto'] ?? false),
            'registration_fee' => $data['registration_fee'] ?? null,
        ];

        if ($itinerary === null) {
            $itinerary = $travelOrder->itineraries()->create($attributes);
        } else {
            $itinerary->update($attributes);
        }

        // A mode used by a leg is necessarily allowed for the trip, even when
        // the header form did not tick it. Keeping the union in the pivot means
        // the show page never lists an empty allowed-mode line.
        if ($modeId !== null) {
            $travelOrder->transportationModes()->syncWithoutDetaching([$modeId]);
        }

        return $itinerary;
    }

    /**
     * The fare configured for a mode, or zero for government transport.
     */
    private function automaticFare(?int $modeId, ?int $toLocationId): ?string
    {
        $mode = $modeId === null
            ? null
            : TransportationMode::query()->with('fares')->find($modeId);

        if ($mode === null) {
            return null;
        }

        if ($mode->is_government) {
            return '0.00';
        }

        return $mode->fareTo($toLocationId) ?? $mode->fix_cost;
    }

    /**
     * Ensure the signed-in user may change the order's content.
     */
    private function authorizeEditor(?User $user, TravelOrder $travelOrder): void
    {
        abort_unless($user instanceof User && $travelOrder->created_by === $user->id, 403);

        abort_unless(
            in_array($travelOrder->status, [TravelOrderStatus::Draft, TravelOrderStatus::Returned], true),
            403,
            'The itinerary can only be changed while the travel order is a draft or returned for revision.',
        );
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
