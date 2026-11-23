import { router } from '@inertiajs/react';
import { Eye, Plus, Printer, Ticket } from 'lucide-react';
import { useState } from 'react';
import { TripTicketStatusBadge } from '@/components/travel-orders/status-badge';
import { TripTicketDialog } from '@/components/travel-orders/trip-ticket-dialog';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import travelOrders from '@/routes/travel-orders';
import type { TravelOrder, TripTicketDriver } from '@/types';

type Props = {
    travelOrder: TravelOrder;
    canCreate: boolean;
    drivers: TripTicketDriver[];
};

function formatDistance(value: string, roundTrip: boolean): string {
    const distance = Number(value);

    if (!Number.isFinite(distance) || distance <= 0) {
        return '—';
    }

    return `${distance.toFixed(2)} km${roundTrip ? ' (round trip)' : ''}`;
}

export function TravelOrderTripTicketPanel({
    travelOrder,
    canCreate,
    drivers,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const tickets = travelOrder.trip_tickets ?? [];

    return (
        <section className="space-y-4 rounded-xl border p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="text-base font-semibold">Trip tickets</h3>
                    <p className="text-sm text-muted-foreground">
                        The vehicle paper issued for this trip.
                    </p>
                </div>
                {canCreate && (
                    <Button size="sm" onClick={() => setCreateOpen(true)}>
                        <Plus className="size-4" />
                        Create trip ticket
                    </Button>
                )}
            </div>

            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Ticket No.</TableHead>
                            <TableHead>Vehicle</TableHead>
                            <TableHead>Driver</TableHead>
                            <TableHead>Distance</TableHead>
                            <TableHead className="text-right">
                                Estimated fuel
                            </TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {tickets.length > 0 ? (
                            tickets.map((ticket) => (
                                <TableRow key={ticket.id}>
                                    <TableCell className="font-medium">
                                        {ticket.ticket_no ?? `#${ticket.id}`}
                                    </TableCell>
                                    <TableCell>
                                        <p className="font-medium">
                                            {ticket.vehicle_plate_no}
                                        </p>
                                        {ticket.vehicle_type && (
                                            <p className="text-xs text-muted-foreground">
                                                {ticket.vehicle_type}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {ticket.driver?.full_name ?? '—'}
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {formatDistance(
                                            ticket.distance_km,
                                            ticket.round_trip,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {Number(ticket.liters) > 0
                                            ? `${Number(ticket.liters).toFixed(2)} L`
                                            : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <TripTicketStatusBadge
                                            status={ticket.status}
                                        />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.visit(
                                                        travelOrders.tripTickets.show(
                                                            [
                                                                travelOrder.id,
                                                                ticket.id,
                                                            ],
                                                        ).url,
                                                    )
                                                }
                                            >
                                                <Eye className="size-4" />
                                                View
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.visit(
                                                        travelOrders.tripTickets.print(
                                                            [
                                                                travelOrder.id,
                                                                ticket.id,
                                                            ],
                                                        ).url,
                                                    )
                                                }
                                            >
                                                <Printer className="size-4" />
                                                Print
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={7}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    <div className="flex flex-col items-center gap-2">
                                        <Ticket className="size-6 text-muted-foreground/50" />
                                        {canCreate
                                            ? 'No trip ticket has been prepared yet.'
                                            : 'No trip ticket yet. Trip tickets are available when a government vehicle will be used.'}
                                    </div>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <TripTicketDialog
                travelOrder={travelOrder}
                drivers={drivers}
                open={createOpen}
                onOpenChange={setCreateOpen}
            />
        </section>
    );
}
