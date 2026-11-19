import type { TravelOrderItinerary } from '@/types/travel-order';

export type ItineraryTotals = {
    transportation: number;
    perDiem: number;
    registrationFee: number;
    grand: number;
};

/**
 * Coerce a money column to a number.
 *
 * The columns come off the API as decimal strings, and a row the form left
 * untouched is stored as null rather than an empty string. Anything that is not
 * a finite number counts as zero so a single malformed row cannot turn the
 * printed total into NaN.
 */
export function amount(value: string | number | null | undefined): number {
    const parsed = Number(value ?? 0);

    return Number.isFinite(parsed) ? parsed : 0;
}

export function itineraryLineTotal(entry: TravelOrderItinerary): number {
    return (
        amount(entry.transportation_fare) +
        amount(entry.per_diem) +
        amount(entry.registration_fee)
    );
}

export function itineraryTotals(
    entries: TravelOrderItinerary[],
): ItineraryTotals {
    const transportation = entries.reduce(
        (total, entry) => total + amount(entry.transportation_fare),
        0,
    );
    const perDiem = entries.reduce(
        (total, entry) => total + amount(entry.per_diem),
        0,
    );
    const registrationFee = entries.reduce(
        (total, entry) => total + amount(entry.registration_fee),
        0,
    );

    return {
        transportation,
        perDiem,
        registrationFee,
        grand: transportation + perDiem + registrationFee,
    };
}
