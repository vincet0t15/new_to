<?php

namespace App\Services;

use App\Enums\TripTicketStatus;
use App\Models\FuelType;
use App\Models\TravelOrder;
use App\Models\TripTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * The trip ticket lifecycle.
 *
 * A ticket is prepared when the travel order will use a government vehicle. It
 * is issued with a permanent number as soon as it is saved, corrected while it
 * is still issued, completed after the trip, and cancelled instead when the
 * trip will not push through. A completed ticket is paper the driver carried,
 * so it is never changed again.
 */
class TripTicketService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(TravelOrder $order, array $data, User $user): TripTicket
    {
        $this->ensureOrderAllowsTicket($order);

        $plate = trim((string) $data['vehicle_plate_no']);

        $duplicate = $order->tripTickets()
            ->where('status', '!=', TripTicketStatus::Cancelled->value)
            ->where('vehicle_plate_no', $plate)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'vehicle_plate_no' => 'This vehicle already has a trip ticket on the travel order.',
            ]);
        }

        return DB::transaction(function () use ($order, $data, $user, $plate): TripTicket {
            $ticket = $order->tripTickets()->create([
                ...$this->attributes($data),
                'travel_order_id' => $order->id,
                'vehicle_plate_no' => $plate,
                'status' => TripTicketStatus::Issued,
                'created_by' => $user->id,
            ]);

            // The generated id is the sequence, so two tickets can never share
            // a number even when they are prepared at the same moment.
            $ticket->forceFill([
                'ticket_no' => 'TT-'.Carbon::now()->format('Y').'-'.str_pad(
                    (string) $ticket->id,
                    5,
                    '0',
                    STR_PAD_LEFT,
                ),
            ])->save();

            return $ticket->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TripTicket $ticket, array $data): TripTicket
    {
        $this->ensureEditable($ticket);

        $plate = trim((string) $data['vehicle_plate_no']);

        $duplicate = $ticket->travelOrder->tripTickets()
            ->whereKeyNot($ticket->getKey())
            ->where('status', '!=', TripTicketStatus::Cancelled->value)
            ->where('vehicle_plate_no', $plate)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'vehicle_plate_no' => 'This vehicle already has a trip ticket on the travel order.',
            ]);
        }

        $attributes = $this->attributes($data);
        $attributes['fuel_receipt_path'] = $this->resolveReceiptPath($ticket, $data);

        $ticket->update([
            ...$attributes,
            'vehicle_plate_no' => $plate,
        ]);

        return $ticket->refresh();
    }

    /**
     * Record the post-trip figures and close the ticket.
     *
     * @param  array<string, mixed>  $data
     */
    public function complete(TripTicket $ticket, array $data, User $user): TripTicket
    {
        if ($ticket->status !== TripTicketStatus::Issued) {
            throw ValidationException::withMessages([
                'status' => 'Only an issued trip ticket can be completed.',
            ]);
        }

        $start = $this->decimal($data['odometer_start'] ?? $ticket->odometer_start);
        $end = $this->decimal($data['odometer_end'] ?? $ticket->odometer_end);

        if ($start !== null && $end !== null && $end < $start) {
            throw ValidationException::withMessages([
                'odometer_end' => 'The ending odometer cannot be lower than the starting odometer.',
            ]);
        }

        $distance = $this->decimal($ticket->distance_km);

        if ($distance === null && $start !== null && $end !== null) {
            $distance = round($end - $start, 2);
        }

        $fuelConsumed = $this->decimal($data['fuel_consumed'] ?? null) ?? $this->decimal($ticket->fuel_consumed);
        $fuelPrice = $this->decimal($data['fuel_price_per_liter'] ?? null) ?? $this->decimal($ticket->fuel_price_per_liter);
        $fuelCost = $this->decimal($data['fuel_cost'] ?? null) ?? $this->decimal($ticket->fuel_cost);

        if ($fuelCost === null && $fuelConsumed !== null && $fuelPrice !== null) {
            $fuelCost = round($fuelConsumed * $fuelPrice, 2);
        }

        $ticket->forceFill([
            'status' => TripTicketStatus::Completed,
            'odometer_start' => $start,
            'odometer_end' => $end,
            'distance_km' => $distance ?? $ticket->distance_km,
            'fuel_consumed' => $fuelConsumed,
            'fuel_price_per_liter' => $fuelPrice,
            'fuel_cost' => $fuelCost,
            'fuel_station' => $this->text($data['fuel_station'] ?? $ticket->fuel_station),
            'fuel_receipt_no' => $this->text($data['fuel_receipt_no'] ?? $ticket->fuel_receipt_no),
            'fuel_receipt_path' => $this->resolveReceiptPath($ticket, $data),
            'remarks' => $this->text($data['remarks'] ?? $ticket->remarks),
            'completed_by' => $user->id,
            'completed_at' => Carbon::now(),
        ])->save();

        return $ticket->refresh();
    }

    public function cancel(TripTicket $ticket, ?string $reason = null): TripTicket
    {
        if (! $ticket->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Only an issued trip ticket can be cancelled.',
            ]);
        }

        $ticket->forceFill([
            'status' => TripTicketStatus::Cancelled,
            'cancelled_at' => Carbon::now(),
            'cancel_reason' => $this->text($reason),
        ])->save();

        return $ticket->refresh();
    }

    /**
     * The editable figures, with the fuel requirement derived from them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $distance = $this->decimal($data['distance_km'] ?? null) ?? 0.0;
        $roundTrip = (bool) ($data['round_trip'] ?? false);
        $efficiency = $this->decimal($data['fuel_efficiency'] ?? null) ?? 0.0;

        $liters = $efficiency > 0
            ? round(($distance * ($roundTrip ? 2 : 1)) / $efficiency, 2)
            : 0.0;

        $fuelPrice = $this->decimal($data['fuel_price_per_liter'] ?? null);
        $fuelCost = $this->decimal($data['fuel_cost'] ?? null);

        if ($fuelCost === null && $fuelPrice !== null && $liters > 0) {
            $fuelCost = round($fuelPrice * $liters, 2);
        }

        $fuelTypeId = $data['fuel_type_id'] ?? null;
        $fuelTypeName = $this->text($data['fuel_type'] ?? null);
        if ($fuelTypeId !== null && $fuelTypeId !== '') {
            $fuelTypeName = FuelType::query()->whereKey($fuelTypeId)->value('name') ?? $fuelTypeName;
        }

        return [
            'vehicle_type' => $this->text($data['vehicle_type'] ?? null),
            'driver_id' => $data['driver_id'] ?? null,
            'fuel_type_id' => $fuelTypeId !== '' ? $fuelTypeId : null,
            'fuel_type' => $fuelTypeName,
            'fuel_source_office_id' => $data['fuel_source_office_id'] ?? null,
            'fuel_efficiency' => $efficiency > 0 ? round($efficiency, 2) : null,
            'distance_km' => round($distance, 2),
            'round_trip' => $roundTrip,
            'odometer_start' => $this->decimal($data['odometer_start'] ?? null),
            'liters' => $liters,
            'fuel_price_per_liter' => $fuelPrice,
            'fuel_cost' => $fuelCost,
            'fuel_station' => $this->text($data['fuel_station'] ?? null),
            'fuel_receipt_no' => $this->text($data['fuel_receipt_no'] ?? null),
            'fuel_receipt_path' => $this->resolveReceiptPath(null, $data),
        ];
    }

    private function ensureOrderAllowsTicket(TravelOrder $order): void
    {
        if (! $order->usesGovernmentVehicle()) {
            throw ValidationException::withMessages([
                'travel_order_id' => 'A trip ticket can only be prepared when a government vehicle will be used.',
            ]);
        }
    }

    private function ensureEditable(TripTicket $ticket): void
    {
        if (! $ticket->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Only an issued trip ticket can be changed.',
            ]);
        }
    }

    private function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveReceiptPath(?TripTicket $ticket, array $data): ?string
    {
        $existing = $ticket?->fuel_receipt_path;
        $remove = (bool) ($data['remove_fuel_receipt'] ?? false);
        $file = $data['fuel_receipt'] ?? null;

        if ($file instanceof UploadedFile) {
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }

            return $file->store('trip-ticket-receipts', 'public');
        }

        if ($remove && $existing) {
            Storage::disk('public')->delete($existing);

            return null;
        }

        return $existing;
    }
}
