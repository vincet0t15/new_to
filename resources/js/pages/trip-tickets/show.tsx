import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Ban, Check, Pencil, Printer, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { TripTicketStatusBadge } from '@/components/travel-orders/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatDateTime } from '@/lib/date-format';
import travelOrders from '@/routes/travel-orders';
import tripTickets from '@/routes/travel-orders/trip-tickets';
import type { TripTicket, TripTicketTravelOrder } from '@/types';

type Props = {
    travelOrder: TripTicketTravelOrder;
    tripTicket: TripTicket;
};

function Detail({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="space-y-0.5">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="text-sm">{value}</dd>
        </div>
    );
}

function money(value: string | null): string {
    if (value === null || value === '') {
        return '—';
    }

    return `${Number(value).toFixed(2)} L`;
}

function dateRange(from: string | null, to: string | null): string {
    if (!from || !to) {
        return '—';
    }

    return `${formatDate(from)} – ${formatDate(to)}`;
}

export default function ShowTripTicket({ travelOrder, tripTicket }: Props) {
    const [completeOpen, setCompleteOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);

    const editable = tripTicket.status === 'issued';
    const cancelled = tripTicket.status === 'cancelled';

    const completeForm = useForm({
        odometer_end: tripTicket.odometer_end ?? '',
        fuel_consumed: tripTicket.fuel_consumed ?? '',
        remarks: tripTicket.remarks ?? '',
    });

    const cancelForm = useForm({
        cancel_reason: tripTicket.cancel_reason ?? '',
    });

    const submitComplete = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        completeForm.post(
            tripTickets.complete([travelOrder.id, tripTicket.id]).url,
            {
                preserveScroll: true,
                onSuccess: () => setCompleteOpen(false),
            },
        );
    };

    const submitCancel = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        cancelForm.post(
            tripTickets.cancel([travelOrder.id, tripTicket.id]).url,
            {
                preserveScroll: true,
                onSuccess: () => setCancelOpen(false),
            },
        );
    };

    const destroy = () => {
        if (!window.confirm('Delete this cancelled trip ticket?')) {
            return;
        }

        router.delete(tripTickets.destroy([travelOrder.id, tripTicket.id]).url);
    };

    return (
        <>
            <Head title={tripTicket.ticket_no ?? 'Trip Ticket'} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        <Button asChild variant="outline" size="sm">
                            <Link href={travelOrders.show(travelOrder.id).url}>
                                <ArrowLeft className="size-4" />
                                Back to order
                            </Link>
                        </Button>
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <Heading
                                    title={
                                        tripTicket.ticket_no ?? 'Trip ticket'
                                    }
                                    description={`${travelOrder.control_no ?? travelOrder.to_number ?? `#${travelOrder.id}`} · ${travelOrder.purpose ?? ''}`}
                                />
                                <TripTicketStatusBadge
                                    status={tripTicket.status}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {editable && (
                            <>
                                <Button asChild variant="outline">
                                    <Link
                                        href={
                                            tripTickets.edit([
                                                travelOrder.id,
                                                tripTicket.id,
                                            ]).url
                                        }
                                    >
                                        <Pencil className="size-4" />
                                        Edit
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => setCancelOpen(true)}
                                >
                                    <Ban className="size-4" />
                                    Cancel ticket
                                </Button>
                                <Button onClick={() => setCompleteOpen(true)}>
                                    <Check className="size-4" />
                                    Complete
                                </Button>
                            </>
                        )}
                        <Button asChild variant="outline">
                            <a
                                href={
                                    tripTickets.print([
                                        travelOrder.id,
                                        tripTicket.id,
                                    ]).url
                                }
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Printer className="size-4" />
                                Print
                            </a>
                        </Button>
                        {cancelled && (
                            <Button variant="destructive" onClick={destroy}>
                                <Trash2 className="size-4" />
                                Delete
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <section className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">Vehicle</h3>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Detail
                                    label="Plate number"
                                    value={tripTicket.vehicle_plate_no}
                                />
                                <Detail
                                    label="Vehicle type"
                                    value={tripTicket.vehicle_type ?? '—'}
                                />
                                <Detail
                                    label="Driver"
                                    value={tripTicket.driver?.full_name ?? '—'}
                                />
                                <Detail
                                    label="Fuel type"
                                    value={tripTicket.fuel_type ?? '—'}
                                />
                                <Detail
                                    label="Fuel efficiency"
                                    value={
                                        tripTicket.fuel_efficiency !== null
                                            ? `${tripTicket.fuel_efficiency} km/L`
                                            : '—'
                                    }
                                />
                            </dl>
                        </section>

                        <section className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Trip figures
                            </h3>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Detail
                                    label="Distance one way"
                                    value={`${tripTicket.distance_km} km`}
                                />
                                <Detail
                                    label="Round trip"
                                    value={
                                        tripTicket.round_trip
                                            ? 'Yes — distance travelled twice'
                                            : 'No'
                                    }
                                />
                                <Detail
                                    label="Estimated fuel requirement"
                                    value={money(tripTicket.liters)}
                                />
                                <Detail
                                    label="Fuel consumed"
                                    value={money(tripTicket.fuel_consumed)}
                                />
                                <Detail
                                    label="Odometer at departure"
                                    value={tripTicket.odometer_start ?? '—'}
                                />
                                <Detail
                                    label="Odometer at return"
                                    value={tripTicket.odometer_end ?? '—'}
                                />
                            </dl>
                            {tripTicket.remarks && (
                                <div className="space-y-1">
                                    <p className="text-xs text-muted-foreground">
                                        Remarks
                                    </p>
                                    <p className="text-sm whitespace-pre-wrap">
                                        {tripTicket.remarks}
                                    </p>
                                </div>
                            )}
                        </section>

                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Lifecycle
                            </h3>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Detail
                                    label="Prepared by"
                                    value={tripTicket.creator?.name ?? '—'}
                                />
                                <Detail
                                    label="Prepared at"
                                    value={formatDateTime(
                                        tripTicket.created_at,
                                    )}
                                />
                                <Detail
                                    label="Completed by"
                                    value={tripTicket.completer?.name ?? '—'}
                                />
                                <Detail
                                    label="Completed at"
                                    value={formatDateTime(
                                        tripTicket.completed_at,
                                    )}
                                />
                                <Detail
                                    label="Cancelled at"
                                    value={formatDateTime(
                                        tripTicket.cancelled_at,
                                    )}
                                />
                                <Detail
                                    label="Cancel reason"
                                    value={tripTicket.cancel_reason ?? '—'}
                                />
                            </dl>
                        </section>
                    </div>

                    <div className="space-y-4">
                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Travel order
                            </h3>
                            <dl className="space-y-3">
                                <Detail
                                    label="Reference"
                                    value={
                                        travelOrder.control_no ??
                                        travelOrder.to_number ??
                                        `#${travelOrder.id}`
                                    }
                                />
                                <Detail
                                    label="Dates"
                                    value={dateRange(
                                        travelOrder.date_from,
                                        travelOrder.date_to,
                                    )}
                                />
                                <Detail
                                    label="Origin"
                                    value={travelOrder.origin?.name ?? '—'}
                                />
                                <Detail
                                    label="Destination"
                                    value={travelOrder.destination?.name ?? '—'}
                                />
                            </dl>
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={travelOrders.show(travelOrder.id).url}
                                >
                                    Open travel order
                                </Link>
                            </Button>
                        </section>
                    </div>
                </div>
            </div>

            <Dialog open={completeOpen} onOpenChange={setCompleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Complete trip ticket</DialogTitle>
                        <DialogDescription>
                            Record the post-trip figures. A completed ticket can
                            no longer be changed or cancelled.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitComplete} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="odometer_end">
                                Odometer at return
                            </Label>
                            <Input
                                id="odometer_end"
                                type="number"
                                min="0"
                                step="0.01"
                                value={completeForm.data.odometer_end}
                                onChange={(event) =>
                                    completeForm.setData(
                                        'odometer_end',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={completeForm.errors.odometer_end}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="fuel_consumed">
                                Fuel consumed (L)
                            </Label>
                            <Input
                                id="fuel_consumed"
                                type="number"
                                min="0"
                                step="0.01"
                                value={completeForm.data.fuel_consumed}
                                onChange={(event) =>
                                    completeForm.setData(
                                        'fuel_consumed',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={completeForm.errors.fuel_consumed}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="remarks">Remarks</Label>
                            <Textarea
                                id="remarks"
                                value={completeForm.data.remarks}
                                onChange={(event) =>
                                    completeForm.setData(
                                        'remarks',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={completeForm.errors.remarks} />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setCompleteOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={completeForm.processing}
                            >
                                Complete ticket
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Cancel trip ticket</DialogTitle>
                        <DialogDescription>
                            The ticket stays on record but is no longer valid
                            for travel.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCancel} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="cancel_reason">
                                Reason for cancelling
                            </Label>
                            <Textarea
                                id="cancel_reason"
                                value={cancelForm.data.cancel_reason}
                                onChange={(event) =>
                                    cancelForm.setData(
                                        'cancel_reason',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={cancelForm.errors.cancel_reason}
                            />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setCancelOpen(false)}
                            >
                                Keep ticket
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={cancelForm.processing}
                            >
                                Cancel ticket
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

ShowTripTicket.layout = {
    breadcrumbs: [{ title: 'Trip Ticket', href: '#' }],
};
