import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { TripTicketForm } from '@/components/travel-orders/trip-ticket-form';
import tripTickets from '@/routes/travel-orders/trip-tickets';
import type {
    TripTicket,
    TripTicketDriver,
    TripTicketTravelOrder,
} from '@/types';

type Props = {
    travelOrder: TripTicketTravelOrder;
    tripTicket: TripTicket;
    drivers: TripTicketDriver[];
};

export default function EditTripTicket({
    travelOrder,
    tripTicket,
    drivers,
}: Props) {
    return (
        <>
            <Head title={`Edit ${tripTicket.ticket_no ?? 'Trip Ticket'}`} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title={`Edit ${tripTicket.ticket_no ?? 'trip ticket'}`}
                    description="Correct the figures while the ticket is still issued."
                />

                <TripTicketForm
                    travelOrder={travelOrder}
                    drivers={drivers}
                    tripTicket={tripTicket}
                    action={{
                        url: tripTickets.update([travelOrder.id, tripTicket.id])
                            .url,
                        method: 'put',
                    }}
                    cancelUrl={
                        tripTickets.show([travelOrder.id, tripTicket.id]).url
                    }
                />
            </div>
        </>
    );
}

EditTripTicket.layout = {
    breadcrumbs: [{ title: 'Edit Trip Ticket', href: '#' }],
};
