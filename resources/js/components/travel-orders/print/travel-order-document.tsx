import {
    PrintLetterhead,
    PrintPage,
    PrintSignature,
    PrintTitle,
} from '@/components/travel-orders/print/document';
import {
    capName,
    formatLongDate,
    listPhrase,
} from '@/components/travel-orders/print/format';
import type { Letterhead, TravelOrder, TravelOrderSignatories } from '@/types';

/**
 * The travel order document itself, without any screen chrome.
 *
 * Shared by the standalone print page and the approval view so an approver sees
 * the exact document that will be printed. Signature lines use the signatories
 * frozen on the document when it was submitted, so a designation changing hands
 * later does not restyle a filed copy.
 */
type Props = {
    travelOrder: TravelOrder;
    letterhead: Letterhead;
    signatories: TravelOrderSignatories;
};

export function TravelOrderDocument({
    travelOrder,
    letterhead,
    signatories,
}: Props) {
    const traveler = travelOrder.employee;
    const requestedBy = traveler
        ? {
              name: traveler.full_name ?? null,
              title: traveler.position?.name ?? null,
          }
        : { name: travelOrder.creator?.name ?? null, title: null };

    const travelPeriod =
        travelOrder.date_from === travelOrder.date_to
            ? formatLongDate(travelOrder.date_from)
            : `Departure: ${formatLongDate(travelOrder.date_from)} — Return: ${formatLongDate(travelOrder.date_to)}`;

    const approver = signatories.mayor_approval;
    const fundCertifier = signatories.budget_office_approval;
    const oic = signatories.oic_approval;

    const documentDate = formatLongDate(
        travelOrder.created_at ?? new Date().toISOString(),
    );

    return (
        <PrintPage>
            <PrintLetterhead letterhead={letterhead} />

            <PrintTitle className="text-[12px]">
                {(
                    travelOrder.office?.office_name ?? letterhead.office_name
                ).toUpperCase()}
            </PrintTitle>
            <PrintTitle className="text-[12px] tracking-[0.2em]">
                TRAVEL ORDER
            </PrintTitle>

            <div className="mt-4 flex justify-between text-[12px]">
                <div className="space-y-0.5">
                    <p>
                        <strong>NUMBER:</strong>{' '}
                        {travelOrder.control_no ??
                            travelOrder.to_number ??
                            `#${travelOrder.id}`}
                    </p>
                    <p>
                        <strong>SERIES:</strong> ______________
                    </p>
                </div>
                <p className="whitespace-nowrap">DATE: {documentDate}</p>
            </div>

            <div className="mt-4 space-y-2 text-[12px]">
                <div className="flex gap-2">
                    <div className="w-[190px] shrink-0 space-y-1">
                        <p className="font-bold">NAME:</p>
                        <p className="font-bold">DESIGNATION:</p>
                    </div>
                    <div className="flex-1 space-y-3">
                        {traveler ? (
                            <div>
                                <p className="font-bold uppercase">
                                    {traveler.full_name}
                                </p>
                                <p>{traveler.position?.name ?? '—'}</p>
                            </div>
                        ) : (
                            <p>—</p>
                        )}
                    </div>
                </div>
                <div className="flex gap-2">
                    <span className="w-[190px] shrink-0 font-bold">
                        OFFICE / AGENCY:
                    </span>
                    <span className="flex-1 whitespace-pre-line">
                        {travelOrder.office?.office_name ?? '—'}
                    </span>
                </div>
                <div className="flex gap-2">
                    <span className="w-[190px] shrink-0 font-bold">
                        OFFICIAL STATION:
                    </span>
                    <span className="flex-1">
                        {travelOrder.official_station?.name ?? '—'}
                    </span>
                </div>
                <div className="flex gap-2">
                    <span className="w-[190px] shrink-0 font-bold">
                        DESTINATION:
                    </span>
                    <span className="flex-1">
                        {travelOrder.destination_location?.name ?? '—'}
                    </span>
                </div>
                <div className="flex gap-2">
                    <span className="w-[190px] shrink-0 font-bold">
                        PERIOD OF TRAVEL:
                    </span>
                    <span className="flex-1">{travelPeriod}</span>
                </div>
                <div className="flex gap-2">
                    <span className="w-[190px] shrink-0 font-bold">
                        PURPOSES:
                    </span>
                    <span className="flex-1 whitespace-pre-line">
                        {travelOrder.purpose || '—'}
                    </span>
                </div>
                <div className="flex gap-2">
                    <span className="w-[190px] shrink-0 font-bold">
                        TRANSPORTATION ALLOWED:
                    </span>
                    <span className="flex-1">
                        {travelOrder.transportation_modes &&
                        travelOrder.transportation_modes.length > 0
                            ? travelOrder.transportation_modes
                                  .map((mode) => mode.name)
                                  .join(', ')
                            : '—'}
                    </span>
                </div>
                <div className="flex gap-2">
                    <span className="w-[190px] shrink-0 font-bold">
                        EXPENSES AUTHORIZED:
                    </span>
                    <span className="flex-1">
                        {listPhrase([
                            ...(travelOrder.expense_types ?? []).map(
                                (expenseType) => expenseType.name,
                            ),
                            ...(travelOrder.other_expenses
                                ? [`Others: ${travelOrder.other_expenses}`]
                                : []),
                        ])}
                    </span>
                </div>
            </div>

            <div className="mt-10 grid grid-cols-2 gap-x-10">
                <div className="space-y-10">
                    <PrintSignature
                        label="REQUESTED BY:"
                        name={capName(requestedBy?.name)}
                        title={requestedBy?.title}
                        align="left"
                        line={false}
                    />
                    <PrintSignature
                        label="CERTIFIED AS TO FUND AVAILABILITY:"
                        name={capName(fundCertifier?.name)}
                        title={
                            fundCertifier?.title_override ??
                            fundCertifier?.position ??
                            fundCertifier?.title
                        }
                        align="left"
                        line={false}
                        note={
                            <>
                                EA NO:{' '}
                                <span className="inline-block w-40 border-b border-black">
                                    &nbsp;
                                </span>
                                <br />
                                DATE:{' '}
                                <span className="inline-block w-48 border-b border-black">
                                    &nbsp;
                                </span>
                            </>
                        }
                    />
                    {oic?.name || oic?.title_override ? (
                        <PrintSignature
                            label="OIC:"
                            name={capName(oic?.name)}
                            title={
                                oic?.title_override ??
                                oic?.position ??
                                oic?.title
                            }
                            align="left"
                            line={false}
                        />
                    ) : null}
                </div>
                <PrintSignature
                    label="APPROVED:"
                    name={capName(approver?.name)}
                    title={
                        approver?.title_override ??
                        approver?.position ??
                        approver?.title
                    }
                    align="left"
                    line={false}
                    className="pt-36"
                />
            </div>
        </PrintPage>
    );
}
