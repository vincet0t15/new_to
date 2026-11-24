import type { ReactNode } from 'react';
import {
    PrintLetterhead,
    PrintPage,
} from '@/components/travel-orders/print/document';
import { TripTicketStatusBadge } from '@/components/travel-orders/status-badge';
import { formatDate } from '@/lib/date-format';
import type {
    Letterhead,
    TravelOrder,
    TravelOrderLookupOption,
    TripTicket,
    TripTicketTravelOrder,
} from '@/types';

/**
 * The trip ticket sheet itself, without any screen chrome.
 *
 * Shared by the standalone print page and the approval view so an approver sees
 * the same document that would be printed. The approval view supplies the
 * destination separately because the full travel order model keeps it under
 * `destination_location` while the print endpoint uses `destination`.
 */
type Props = {
    travelOrder: TravelOrder | TripTicketTravelOrder;
    tripTicket: TripTicket;
    letterhead: Letterhead;
    destination?: TravelOrderLookupOption | null;
};

function Field({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="space-y-0.5">
            <p className="text-[10px] tracking-[0.08em] text-neutral-500 uppercase">
                {label}
            </p>
            <p className="text-[12px] font-medium">{value ?? '—'}</p>
        </div>
    );
}

function Signature({ label, name }: { label: string; name?: string | null }) {
    return (
        <div className="text-center">
            <p className="text-[10px] tracking-[0.08em] text-neutral-500 uppercase">
                {label}
            </p>
            <p className="mt-6 border-t border-neutral-400 pt-1 text-[12px] font-medium">
                {name ?? '\u00A0'}
            </p>
        </div>
    );
}

function dateRange(from: string | null, to: string | null): string {
    if (!from || !to) {
        return '—';
    }

    return `${formatDate(from)} – ${formatDate(to)}`;
}

function liters(value: string | null): string {
    if (value === null || value === '') {
        return '—';
    }

    return `${Number(value).toFixed(2)} L`;
}

function locationName(
    value: TravelOrderLookupOption | string | null | undefined,
): string {
    if (!value) {
        return '—';
    }

    return typeof value === 'string' ? value : value.name;
}

export function TripTicketDocument({
    travelOrder,
    tripTicket,
    letterhead,
    destination,
}: Props) {
    const displayDestination =
        destination ??
        ('destination_location' in travelOrder
            ? travelOrder.destination_location
            : travelOrder.destination) ??
        null;

    return (
        <PrintPage>
            <PrintLetterhead letterhead={letterhead} />

            <p className="text-center text-[13px] font-bold tracking-[0.12em] uppercase">
                {travelOrder.office?.office_name ?? letterhead.office_name}
            </p>
            <p className="mt-2 text-center text-[12px] font-bold tracking-[0.15em]">
                TRIP TICKET
            </p>

            <div className="mt-4 flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <p className="text-[12px]">
                        <span className="text-neutral-500">Ticket No.: </span>
                        <span className="font-semibold">
                            {tripTicket.ticket_no ?? '—'}
                        </span>
                    </p>
                    <p className="text-[12px]">
                        <span className="text-neutral-500">Travel Order: </span>
                        {travelOrder.control_no ??
                            travelOrder.to_number ??
                            `#${travelOrder.id}`}
                    </p>
                </div>
                <TripTicketStatusBadge status={tripTicket.status} />
            </div>

            <div className="mt-4 grid gap-3 border-y border-neutral-300 py-3 sm:grid-cols-2">
                <Field label="Purpose" value={travelOrder.purpose ?? '—'} />
                <Field
                    label="Travel dates"
                    value={dateRange(
                        travelOrder.date_from,
                        travelOrder.date_to,
                    )}
                />
                <Field
                    label="Origin"
                    value={locationName(travelOrder.origin)}
                />
                <Field
                    label="Destination"
                    value={locationName(displayDestination)}
                />
            </div>

            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                <Field
                    label="Plate number"
                    value={tripTicket.vehicle_plate_no}
                />
                <Field
                    label="Vehicle type"
                    value={tripTicket.vehicle_type ?? '—'}
                />
                <Field
                    label="Driver"
                    value={tripTicket.driver?.full_name ?? '—'}
                />
                <Field
                    label="Fuel type"
                    value={
                        tripTicket.fuel_type_record?.name ??
                        tripTicket.fuel_type ??
                        '—'
                    }
                />
                <Field
                    label="Fuel efficiency"
                    value={
                        tripTicket.fuel_efficiency !== null
                            ? `${tripTicket.fuel_efficiency} km/L`
                            : '—'
                    }
                />
                <Field
                    label="Round trip"
                    value={tripTicket.round_trip ? 'Yes' : 'No'}
                />
            </div>

            <div className="mt-4 grid gap-3 border-t border-neutral-300 pt-3 sm:grid-cols-2">
                <Field
                    label="Distance one way"
                    value={`${tripTicket.distance_km} km`}
                />
                <Field
                    label="Estimated fuel requirement"
                    value={liters(tripTicket.liters)}
                />
                <Field
                    label="Fuel price / liter"
                    value={
                        tripTicket.fuel_price_per_liter
                            ? `PHP ${Number(tripTicket.fuel_price_per_liter).toFixed(2)}`
                            : '—'
                    }
                />
                <Field
                    label="Fuel cost"
                    value={
                        tripTicket.fuel_cost
                            ? `PHP ${Number(tripTicket.fuel_cost).toFixed(2)}`
                            : '—'
                    }
                />
                <Field
                    label="Fuel station"
                    value={tripTicket.fuel_station ?? '—'}
                />
                <Field
                    label="Receipt / invoice no."
                    value={tripTicket.fuel_receipt_no ?? '—'}
                />
                <Field
                    label="Receipt attachment"
                    value={
                        tripTicket.fuel_receipt_url
                            ? 'Attached (see digital record)'
                            : '—'
                    }
                />
                <Field
                    label="Odometer at departure"
                    value={tripTicket.odometer_start ?? '—'}
                />
                <Field
                    label="Odometer at return"
                    value={tripTicket.odometer_end ?? '—'}
                />
                <Field
                    label="Fuel consumed"
                    value={liters(tripTicket.fuel_consumed)}
                />
                <Field label="Remarks" value={tripTicket.remarks ?? '—'} />
            </div>

            {(travelOrder.itineraries ?? []).length > 0 && (
                <div className="mt-4 border-t border-neutral-300 pt-3">
                    <p className="mb-2 text-[10px] tracking-[0.08em] text-neutral-500 uppercase">
                        Itinerary
                    </p>
                    <table className="w-full border-collapse text-[11px]">
                        <thead>
                            <tr className="border-b border-neutral-300 text-left">
                                <th className="py-1 pr-2 font-semibold">
                                    Date
                                </th>
                                <th className="py-1 pr-2 font-semibold">
                                    From
                                </th>
                                <th className="py-1 pr-2 font-semibold">To</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(travelOrder.itineraries ?? []).map((leg) => (
                                <tr
                                    key={leg.id}
                                    className="border-b border-neutral-200"
                                >
                                    <td className="py-1 pr-2 whitespace-nowrap">
                                        {dateRange(leg.date_from, leg.date_to)}
                                    </td>
                                    <td className="py-1 pr-2">
                                        {leg.from_location?.name ?? '—'}
                                    </td>
                                    <td className="py-1 pr-2">
                                        {leg.to_location?.name ?? '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <div className="mt-10 grid grid-cols-3 gap-6">
                <Signature label="Driver" name={tripTicket.driver?.full_name} />
                <Signature
                    label="Prepared by"
                    name={tripTicket.creator?.name}
                />
                <Signature label="Approved by" />
            </div>
        </PrintPage>
    );
}
