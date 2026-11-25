import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { TripTicketForm } from '@/components/travel-orders/trip-ticket-form';
import tripTickets from '@/routes/travel-orders/trip-tickets';
import type {
    TripTicket,
    TripTicketDriver,
    TripTicketTravelOrder,
    VehicleOption,
} from '@/types';

type Props = {
    travelOrder: TripTicketTravelOrder;
    tripTicket: TripTicket;
    vehicles: VehicleOption[];
    drivers: TripTicketDriver[];
    offices: { id: number; office_name: string }[];
    fuelTypes: { id: number; name: string }[];
};

export default function EditTripTicket({
    travelOrder,
    tripTicket,
    vehicles,
    drivers,
    offices,
    fuelTypes,
}: Props) {
    return (
        <>
            <Head title={`Edit ${tripTicket.ticket_no ?? 'Trip Ticket'}`} />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title={`Edit ${tripTicket.ticket_no ?? 'trip ticket'}`}
                    description="Correct the figures while the ticket is still issued."
                />

                <TripTicketForm
                    travelOrder={travelOrder}
                    vehicles={vehicles}
                    drivers={drivers}
                    offices={offices}
                    fuelTypes={fuelTypes}
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
