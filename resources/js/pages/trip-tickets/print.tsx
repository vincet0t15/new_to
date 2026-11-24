import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { TripTicketDocument } from '@/components/travel-orders/print/trip-ticket-document';
import { Button } from '@/components/ui/button';
import tripTickets from '@/routes/travel-orders/trip-tickets';
import type { Letterhead, TripTicket, TripTicketTravelOrder } from '@/types';

type Props = {
    travelOrder: TripTicketTravelOrder;
    tripTicket: TripTicket;
    letterhead: Letterhead;
};

export default function PrintTripTicket({
    travelOrder,
    tripTicket,
    letterhead,
}: Props) {
    return (
        <>
            <Head title={`Print ${tripTicket.ticket_no ?? 'Trip Ticket'}`} />

            <div className="min-h-screen bg-muted/40 py-6 text-black print:bg-white print:py-0">
                <div className="mx-auto mb-4 flex w-full max-w-[8.5in] flex-wrap items-center justify-between gap-2 px-4 print:hidden">
                    <Button asChild variant="outline" size="sm">
                        <Link
                            href={
                                tripTickets.show([
                                    travelOrder.id,
                                    tripTicket.id,
                                ]).url
                            }
                        >
                            <ArrowLeft className="size-4" />
                            Back
                        </Link>
                    </Button>
                    <Button size="sm" onClick={() => window.print()}>
                        <Printer className="size-4" />
                        Print
                    </Button>
                </div>

                <TripTicketDocument
                    travelOrder={travelOrder}
                    tripTicket={tripTicket}
                    letterhead={letterhead}
                />
            </div>
        </>
    );
}

PrintTripTicket.layout = {
    breadcrumbs: [{ title: 'Print Trip Ticket', href: '#' }],
};
