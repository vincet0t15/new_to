import { Head } from '@inertiajs/react';
import { PrintDocument } from '@/components/travel-orders/print/document';
import { TravelOrderDocument } from '@/components/travel-orders/print/travel-order-document';
import type { Letterhead, TravelOrder, TravelOrderSignatories } from '@/types';

/**
 * The travel order form itself.
 *
 * The document body is shared with the approval view so an approver sees the
 * exact sheet that will be printed without leaving the review page.
 */
type PrintOrderProps = {
    travelOrder: TravelOrder;
    letterhead: Letterhead;
    signatories: TravelOrderSignatories;
};

export default function PrintTravelOrder({
    travelOrder,
    letterhead,
    signatories,
}: PrintOrderProps) {
    return (
        <>
            <Head title="Print Travel Order" />

            <PrintDocument>
                <TravelOrderDocument
                    travelOrder={travelOrder}
                    letterhead={letterhead}
                    signatories={signatories}
                />
            </PrintDocument>
        </>
    );
}
