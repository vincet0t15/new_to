import { TravelOrderStatusBadge } from '@/components/travel-orders/status-badge';
import type { TravelOrder } from '@/types/travel-order';

function Value({ children }: { children: React.ReactNode }) {
    return <dd className="font-medium">{children ?? '—'}</dd>;
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
                        {travelOrder.destinationLocation?.name ??
                            travelOrder.destination}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Origin</dt>
                    <Value>{travelOrder.origin?.name}</Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Official station</dt>
                    <Value>{travelOrder.officialStation?.name}</Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Dates</dt>
                    <Value>
                        {travelOrder.date_from} – {travelOrder.date_to}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Classification</dt>
                    <Value>
                        {travelOrder.travelClassification?.name ??
                            travelOrder.travel_classification}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">Fund source</dt>
                    <Value>
                        {travelOrder.fundSource?.name ??
                            travelOrder.fund_source}
                    </Value>
                </div>
                <div>
                    <dt className="text-muted-foreground">
                        Transportation modes
                    </dt>
                    <Value>
                        {travelOrder.transportationModes?.length
                            ? travelOrder.transportationModes
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
                        {travelOrder.expenseTypes?.length
                            ? travelOrder.expenseTypes
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
