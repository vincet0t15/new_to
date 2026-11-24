import { Head } from '@inertiajs/react';
import { PrintDocument } from '@/components/travel-orders/print/document';
import { ItineraryDocument } from '@/components/travel-orders/print/itinerary-document';
import type { ItinerarySignatories, Letterhead, TravelOrder } from '@/types';

/**
 * Appendix A — Actual Itinerary of Travel.
 *
 * The document body is shared with the approval view so an approver can flip
 * between the travel order and its itinerary without leaving the review page.
 */
type PrintItineraryProps = {
    travelOrder: TravelOrder;
    letterhead: Letterhead;
    itinerarySignatories?: ItinerarySignatories;
};

export default function PrintItinerary({
    travelOrder,
    letterhead,
    itinerarySignatories,
}: PrintItineraryProps) {
    return (
        <>
            <Head title="Print Itinerary of Travel" />

            <PrintDocument>
                <ItineraryDocument
                    travelOrder={travelOrder}
                    letterhead={letterhead}
                    itinerarySignatories={itinerarySignatories}
                />
            </PrintDocument>
        </>
    );
}
