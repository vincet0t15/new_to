import {
    PrintLetterhead,
    PrintPage,
    PrintSignature,
} from '@/components/travel-orders/print/document';
import {
    capName,
    formatDisplayTime,
    formatEntryDateRange,
    formatMoney,
    formatShortDate,
} from '@/components/travel-orders/print/format';
import {
    itineraryLineTotal,
    itineraryTotals,
} from '@/components/travel-orders/print/totals';
import type { ItinerarySignatories, Letterhead, TravelOrder } from '@/types';

/**
 * Appendix A — Actual Itinerary of Travel, without any screen chrome.
 *
 * Shared by the standalone print page and the approval view so an approver sees
 * the exact sheet that will be printed. An office's itinerary signatory group
 * names the certified/approved lines; anything it leaves out falls back to the
 * standard role title, exactly as an office without a group has always printed.
 */
type Props = {
    travelOrder: TravelOrder;
    letterhead: Letterhead;
    itinerarySignatories?: ItinerarySignatories;
};

export function ItineraryDocument({
    travelOrder,
    letterhead,
    itinerarySignatories,
}: Props) {
    const certified = itinerarySignatories?.certified ?? {
        name: null,
        title: 'Municipal Budget Officer',
    };
    const approved = itinerarySignatories?.approved ?? {
        name: null,
        title: 'Municipal Mayor',
    };
    const traveler = travelOrder.employee;
    const itineraries = travelOrder.itineraries ?? [];
    const totals = itineraryTotals(itineraries);

    return (
        <PrintPage>
            <p className="mb-2 text-right text-[12px] font-bold">
                APPENDIX &quot;A&quot;
            </p>

            <PrintLetterhead letterhead={letterhead} />

            <p className="text-center text-[12px] font-bold uppercase">
                {traveler?.office?.office_name ??
                    travelOrder.office?.office_name ??
                    letterhead.office_name}
            </p>
            <p className="mt-1 text-center text-[12px] font-bold">
                ITINERARY OF TRAVEL
            </p>
            <p className="mt-1 text-right text-[12px] text-neutral-700">
                Date Prepared:{' '}
                {formatShortDate(
                    travelOrder.created_at ?? new Date().toISOString(),
                )}
            </p>

            <div className="mt-4 space-y-2">
                <div className="flex gap-2 text-[12px]">
                    <span className="w-[150px] shrink-0 font-bold">Name:</span>
                    <span className="flex-1 border-b border-neutral-400 pb-0.5">
                        {traveler?.full_name ?? '—'}
                    </span>
                </div>
                <div className="flex gap-2 text-[12px]">
                    <span className="w-[150px] shrink-0 font-bold">
                        Position:
                    </span>
                    <span className="flex-1 border-b border-neutral-400 pb-0.5">
                        {traveler?.position?.name ?? '—'}
                    </span>
                </div>
                <div className="flex gap-2 text-[12px]">
                    <span className="w-[150px] shrink-0 font-bold">
                        Station:
                    </span>
                    <span className="flex-1 border-b border-neutral-400 pb-0.5">
                        {letterhead.station_line ??
                            travelOrder.official_station?.name ??
                            letterhead.official_station}
                    </span>
                </div>
                <div className="flex gap-2 text-[12px]">
                    <span className="w-[150px] shrink-0 font-bold">
                        Monthly Salary:
                    </span>
                    <span className="flex-1 border-b border-neutral-400 pb-0.5">
                        {traveler?.monthly_salary
                            ? `₱${Number(traveler.monthly_salary).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
                            : '—'}
                    </span>
                </div>
                <div className="flex gap-2 text-[12px]">
                    <span className="w-[150px] shrink-0 font-bold">
                        Purpose of Travel:
                    </span>
                    <span className="flex-1 border-b border-neutral-400 pb-0.5">
                        {travelOrder.purpose || '—'}
                    </span>
                </div>
            </div>

            <table className="mt-3 w-full border-collapse text-[12px]">
                <thead>
                    <tr>
                        <th
                            rowSpan={2}
                            className="border border-black bg-[#F1EFE8] px-2 py-1 text-left"
                        >
                            Date
                        </th>
                        <th
                            rowSpan={2}
                            className="border border-black bg-[#F1EFE8] px-2 py-1 text-left"
                        >
                            Place to be Visited
                        </th>
                        <th
                            colSpan={2}
                            className="border border-black bg-[#F1EFE8] px-2 py-1 text-center"
                        >
                            Time
                        </th>
                        <th
                            rowSpan={2}
                            className="border border-black bg-[#F1EFE8] px-2 py-1 text-left"
                        >
                            Means of
                            <br />
                            Transportation
                        </th>
                        <th
                            rowSpan={2}
                            className="border border-black bg-[#F1EFE8] px-2 py-1 text-center"
                        >
                            Transportation
                            <br />
                            Fare
                        </th>
                        <th
                            rowSpan={2}
                            className="border border-black bg-[#F1EFE8] px-2 py-1 text-center"
                        >
                            Per Diems
                        </th>
                        <th
                            rowSpan={2}
                            className="border border-black bg-[#F1EFE8] px-2 py-1 text-center"
                        >
                            Registration
                            <br />
                            Fee
                        </th>
                        <th
                            rowSpan={2}
                            className="border border-black bg-[#F1EFE8] px-2 py-1 text-right"
                        >
                            Total
                            <br />
                            Amount
                        </th>
                    </tr>
                    <tr>
                        <th className="border border-black bg-[#F1EFE8] px-2 py-1 text-center">
                            Departure
                        </th>
                        <th className="border border-black bg-[#F1EFE8] px-2 py-1 text-center">
                            Arrival
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {itineraries.length === 0 ? (
                        <tr>
                            <td
                                colSpan={9}
                                className="border border-black px-2 py-1.5 text-center"
                            >
                                No itinerary rows recorded.
                            </td>
                        </tr>
                    ) : (
                        itineraries.map((entry) => (
                            <tr key={entry.id} className="align-top">
                                <td className="border border-black px-2 py-1.5 whitespace-nowrap">
                                    {formatEntryDateRange(
                                        entry.date_from,
                                        entry.date_to,
                                    )}
                                </td>
                                <td className="border border-black px-2 py-1.5">
                                    {entry.reason ||
                                        entry.to_location?.name ||
                                        entry.from_location?.name ||
                                        '—'}
                                </td>
                                <td className="border border-black px-2 py-1.5 text-center whitespace-nowrap">
                                    {formatDisplayTime(entry.departure_time)}
                                </td>
                                <td className="border border-black px-2 py-1.5 text-center whitespace-nowrap">
                                    {formatDisplayTime(entry.arrival_time)}
                                </td>
                                <td className="border border-black px-2 py-1.5">
                                    {entry.transportation_mode?.name ?? ''}
                                </td>
                                <td className="border border-black px-2 py-1.5 text-center whitespace-nowrap">
                                    {formatMoney(entry.transportation_fare)}
                                </td>
                                <td className="border border-black px-2 py-1.5 text-center whitespace-nowrap">
                                    {formatMoney(entry.per_diem)}
                                </td>
                                <td className="border border-black px-2 py-1.5 text-center whitespace-nowrap">
                                    {formatMoney(entry.registration_fee)}
                                </td>
                                <td className="border border-black px-2 py-1.5 text-right whitespace-nowrap">
                                    {formatMoney(itineraryLineTotal(entry))}
                                </td>
                            </tr>
                        ))
                    )}
                    <tr className="font-bold">
                        <td
                            colSpan={5}
                            className="border border-black px-2 py-1.5 text-right"
                        >
                            TOTAL
                        </td>
                        <td className="border border-black px-2 py-1.5 text-center whitespace-nowrap">
                            {formatMoney(totals.transportation)}
                        </td>
                        <td className="border border-black px-2 py-1.5 text-center whitespace-nowrap">
                            {formatMoney(totals.perDiem)}
                        </td>
                        <td className="border border-black px-2 py-1.5 text-center whitespace-nowrap">
                            {formatMoney(totals.registrationFee)}
                        </td>
                        <td className="border border-black px-2 py-1.5 text-right whitespace-nowrap">
                            {formatMoney(totals.grand)}
                        </td>
                    </tr>
                </tbody>
            </table>

            <p className="mt-3 text-justify text-[12px] leading-relaxed">
                I HEREBY CERTIFY that: (1) I have reviewed the foregoing
                itinerary; (2) the travel is necessary to the service; (3) the
                period covered is reasonable; and (4) the expenses claimed are
                proper.
            </p>

            <div className="mt-11 grid grid-cols-3 gap-x-6">
                <PrintSignature
                    name={capName(traveler?.full_name)}
                    note={
                        <>
                            {traveler?.position?.name ?? ' '}
                            <br />
                            (Prepared by)
                        </>
                    }
                />
                <PrintSignature
                    name={capName(certified.name) || ' '}
                    note={
                        <>
                            {certified.title}
                            <br />
                            (Certified/Reviewed by)
                        </>
                    }
                />
                <PrintSignature
                    name={capName(approved.name) || ' '}
                    note={
                        <>
                            {approved.title}
                            <br />
                            (Approved by)
                        </>
                    }
                />
            </div>
        </PrintPage>
    );
}
