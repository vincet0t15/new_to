import { TravelOrderStatusBadge } from '@/components/travel-orders/status-badge';
import { formatDate } from '@/lib/date-format';
import type {
    TravelOrder,
    TravelOrderLookupOption,
} from '@/types/travel-order';

function Value({ children }: { children: React.ReactNode }) {
    return <dd className="font-medium">{children ?? '—'}</dd>;
}

function formatDateRange(from: string | null, to: string | null): string {
    if (!from && !to) {
        return '—';
    }

    if (!from || !to) {
        return formatDate(from ?? to);
    }

    return `${formatDate(from)} – ${formatDate(to)}`;
}

/**
 * The legacy text columns and their lookup relations share a name.
 *
 * Eloquent writes the loaded relation to the same snake_case key as the
 * column, so on the detail screens the value can be either the mirrored
 * name or the lookup object. Accept both instead of rendering the object.
 */
function lookupName(
    value: string | TravelOrderLookupOption | null | undefined,
): string | null {
    if (typeof value === 'string') {
        return value;
    }

    return value?.name ?? null;
}

export function TravelOrderDetails({
    travelOrder,
}: {
    travelOrder: TravelOrder;
}) {
    return (
        <section className="space-y-3 rounded-xl border p-4">
            <h3 className="text-base font-semibold">Details</h3>
            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt className="text-muted-foreground">Control No.</dt>
                    <Value>
                        {travelOrder.control_no ??
                            travelOrder.to_number ??
                            `#${travelOrder.id}`}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Status</dt>
                    <dd>
                        <TravelOrderStatusBadge status={travelOrder.status} />
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Employee</dt>
                    <Value>
                        {travelOrder.employee?.full_name ??
                            travelOrder.creator?.name ??
                            '—'}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Office</dt>
                    <Value>{travelOrder.office?.office_name}</Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Purpose</dt>
                    <Value>{travelOrder.purpose}</Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Destination</dt>
                    <Value>
                        {travelOrder.destination_location?.name ??
                            travelOrder.destination}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Origin</dt>
                    <Value>{travelOrder.origin?.name}</Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Official station</dt>
                    <Value>{travelOrder.official_station?.name}</Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Dates</dt>
                    <Value>
                        {formatDateRange(
                            travelOrder.date_from,
                            travelOrder.date_to,
                        )}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Classification</dt>
                    <Value>
                        {lookupName(travelOrder.travel_classification)}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Fund source</dt>
                    <Value>{lookupName(travelOrder.fund_source)}</Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Earmarked</dt>
                    <Value>
                        {travelOrder.earmarked_at
                            ? `${formatDate(travelOrder.earmarked_at)}${travelOrder.earmark_remarks ? ` — ${travelOrder.earmark_remarks}` : ''}`
                            : null}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">
                        Recorded / Distributed
                    </dt>
                    <Value>
                        {travelOrder.recorded_at
                            ? `${formatDate(travelOrder.recorded_at)}${travelOrder.distributed_at ? ' · Distributed' : ''}${travelOrder.recording_remarks ? ` — ${travelOrder.recording_remarks}` : ''}`
                            : null}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">
                        Transportation modes
                    </dt>
                    <Value>
                        {travelOrder.transportation_modes?.length
                            ? travelOrder.transportation_modes
                                  .map((mode) => mode.name)
                                  .join(', ')
                            : null}
                    </Value>
                </div>
                <div className="sm:col-span-2">
                    <dt className="text-muted-foreground">
                        Expenses authorized
                    </dt>
                    <Value>
                        {travelOrder.expense_types?.length
                            ? travelOrder.expense_types
                                  .map((expenseType) => expenseType.name)
                                  .join(', ')
                            : null}
                        {travelOrder.other_expenses
                            ? ` — ${travelOrder.other_expenses}`
                            : ''}
                    </Value>
                </div>
            </dl>
            {travelOrder.submission_notes && (
                <div className="mt-2">
                    <p className="text-sm text-muted-foreground">
                        Notes for approvers
                    </p>
                    <p className="text-sm">{travelOrder.submission_notes}</p>
                </div>
            )}
        </section>
    );
}
