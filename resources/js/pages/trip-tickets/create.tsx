import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { TripTicketForm } from '@/components/travel-orders/trip-ticket-form';
import travelOrders from '@/routes/travel-orders';
import tripTickets from '@/routes/travel-orders/trip-tickets';
import type { TripTicketDriver, TripTicketTravelOrder } from '@/types';

type Props = {
    travelOrder: TripTicketTravelOrder;
    drivers: TripTicketDriver[];
    offices: { id: number; office_name: string }[];
    fuelTypes: { id: number; name: string }[];
};

export default function CreateTripTicket({
    travelOrder,
    drivers,
    offices,
    fuelTypes,
}: Props) {
    return (
        <>
            <Head title="New Trip Ticket" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="New trip ticket"
                    description="Prepare the ticket for a travel order that uses a government vehicle. It is numbered as soon as it is saved."
                />

                <TripTicketForm
                    travelOrder={travelOrder}
                    drivers={drivers}
                    offices={offices}
                    fuelTypes={fuelTypes}
                    action={{
                        url: tripTickets.store(travelOrder.id).url,
                        method: 'post',
                    }}
                    cancelUrl={travelOrders.show(travelOrder.id).url}
                />
            </div>
        </>
    );
}

CreateTripTicket.layout = {
    breadcrumbs: [{ title: 'New Trip Ticket', href: '#' }],
};
