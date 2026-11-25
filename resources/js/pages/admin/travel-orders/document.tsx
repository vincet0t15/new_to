import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState, type KeyboardEvent } from 'react';
import Heading from '@/components/heading';
import { ItineraryDocument } from '@/components/travel-orders/print/itinerary-document';
import { TravelOrderDocument } from '@/components/travel-orders/print/travel-order-document';
import { TripTicketDocument } from '@/components/travel-orders/print/trip-ticket-document';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type {
    ItinerarySignatories,
    Letterhead,
    TravelOrder,
    TravelOrderSignatories,
    TripTicket,
} from '@/types';

const documentTabs = [
    ['order', 'Travel Order'],
    ['itinerary', 'Itinerary'],
    ['trip-ticket', 'Trip Ticket'],
] as const;

type DocumentTab = (typeof documentTabs)[number][0];

type Props = {
    travelOrder: TravelOrder;
    letterhead: Letterhead;
    signatories: TravelOrderSignatories;
    itinerarySignatories: ItinerarySignatories;
    tripTickets: TripTicket[];
    urls: { register: string };
};

function reference(order: TravelOrder): string {
    return order.control_no ?? order.to_number ?? `#${order.id}`;
}

export default function AdminTravelOrderDocument({
    travelOrder,
    letterhead,
    signatories,
    itinerarySignatories,
    tripTickets,
    urls,
}: Props) {
    const [documentTab, setDocumentTab] = useState<DocumentTab>('order');

    const onTabKeyDown = (
        event: KeyboardEvent<HTMLButtonElement>,
        key: DocumentTab,
    ) => {
        const currentIndex = documentTabs.findIndex(([value]) => value === key);
        let nextIndex = currentIndex;

        if (event.key === 'ArrowRight') {
            nextIndex = (currentIndex + 1) % documentTabs.length;
        } else if (event.key === 'ArrowLeft') {
            nextIndex =
                (currentIndex - 1 + documentTabs.length) % documentTabs.length;
        } else if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = documentTabs.length - 1;
        } else {
            return;
        }

        event.preventDefault();
        setDocumentTab(documentTabs[nextIndex][0]);
        document
            .getElementById(`document-tab-${documentTabs[nextIndex][0]}`)
            ?.focus();
    };

    return (
        <>
            <Head title={`Travel Order ${reference(travelOrder)}`} />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex flex-wrap items-center gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={urls.register}>
                            <ArrowLeft className="size-4" />
                            Register
                        </Link>
                    </Button>
                    <Heading
                        title={`Travel Order ${reference(travelOrder)}`}
                        description="Read-only view of the documents issued for this travel order."
                    />
                </div>

                <section className="overflow-hidden rounded-xl border bg-muted/40">
                    <div
                        className="flex gap-1 border-b bg-background px-3 pt-3"
                        role="tablist"
                        aria-label="Document preview"
                    >
                        {documentTabs.map(([key, label]) => (
                            <button
                                key={key}
                                id={`document-tab-${key}`}
                                type="button"
                                role="tab"
                                aria-selected={documentTab === key}
                                aria-controls="document-tabpanel"
                                tabIndex={documentTab === key ? 0 : -1}
                                className={`rounded-t-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                    documentTab === key
                                        ? 'bg-muted text-foreground'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                                onClick={() => setDocumentTab(key)}
                                onKeyDown={(event) => onTabKeyDown(event, key)}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                    <div
                        id="document-tabpanel"
                        className="p-3"
                        role="tabpanel"
                        aria-labelledby={`document-tab-${documentTab}`}
                    >
                        {documentTab === 'order' ? (
                            <TravelOrderDocument
                                travelOrder={travelOrder}
                                letterhead={letterhead}
                                signatories={signatories}
                            />
                        ) : documentTab === 'itinerary' ? (
                            <ItineraryDocument
                                travelOrder={travelOrder}
                                letterhead={letterhead}
                                itinerarySignatories={itinerarySignatories}
                            />
                        ) : tripTickets.length > 0 ? (
                            <div className="space-y-4">
                                {tripTickets.map((tripTicket) => (
                                    <TripTicketDocument
                                        key={tripTicket.id}
                                        travelOrder={travelOrder}
                                        tripTicket={tripTicket}
                                        letterhead={letterhead}
                                        destination={
                                            travelOrder.destination_location
                                        }
                                    />
                                ))}
                            </div>
                        ) : (
                            <div
                                role="status"
                                className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                            >
                                No trip ticket has been issued for this travel
                                order.
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

AdminTravelOrderDocument.layout = {
    breadcrumbs: [
        {
            title: 'Travel Order Register',
            href: admin.travelOrders.index().url,
        },
    ],
};
