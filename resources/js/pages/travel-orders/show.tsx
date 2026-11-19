import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Send, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { ActionTimeline } from '@/components/travel-orders/action-timeline';
import { TravelOrderItineraryPanel } from '@/components/travel-orders/itinerary-panel';
import { RevisionTimeline } from '@/components/travel-orders/revision-timeline';
import { TravelOrderDetails } from '@/components/travel-orders/travel-order-details';
import { TravelOrderTripTicketPanel } from '@/components/travel-orders/trip-ticket-panel';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/date-format';
import travelOrdersRoutes from '@/routes/travel-orders';
import type {
    TravelOrder,
    TravelOrderAbilities,
    TravelOrderRevision,
    TravelOrderRouteStep,
    TravelOrderShowOptions,
    TravelOrderTimelineEntry,
} from '@/types';

type Props = {
    travelOrder: TravelOrder;
    route: TravelOrderRouteStep[];
    timeline: TravelOrderTimelineEntry[];
    revisions: TravelOrderRevision[];
    options: TravelOrderShowOptions;
    can: TravelOrderAbilities;
    urls: {
        index: string;
        edit: string;
        submit: string;
        resubmit: string;
        destroy: string;
        tripTicketCreate: string;
    };
};

export default function ShowTravelOrder({
    travelOrder,
    timeline,
    revisions,
    options,
    can,
    urls,
}: Props) {
    const submit = () => {
        router.post(urls.submit, {}, { preserveScroll: true });
    };

    const resubmit = () => {
        router.visit(urls.edit);
    };

    const destroy = () => {
        if (!confirm('Delete this travel order?')) {
            return;
        }

        router.delete(urls.destroy);
    };

    return (
        <>
            <Head title={`Travel Order #${travelOrder.id}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => router.visit(urls.index)}
                    >
                        <ArrowLeft className="size-4" />
                        Back
                    </Button>
                    <Heading
                        title={`Travel Order #${travelOrder.id}`}
                        description="Details and audit trail."
                    />
                    <div className="ml-auto flex flex-wrap gap-2">
                        {travelOrder.status === 'returned' && (
                            <div className="mr-auto w-full rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-800 dark:text-amber-300">
                                <p className="font-medium">
                                    Returned for revision
                                    {travelOrder.returned_from_step
                                        ? ` at ${travelOrder.returned_from_step}`
                                        : ''}
                                    {travelOrder.returned_at
                                        ? ` on ${formatDateTime(travelOrder.returned_at)}`
                                        : ''}
                                    {travelOrder.returnedBy?.name
                                        ? ` by ${travelOrder.returnedBy.name}`
                                        : ''}
                                </p>
                                {travelOrder.return_remarks && (
                                    <p className="mt-1 whitespace-pre-wrap">
                                        {travelOrder.return_remarks}
                                    </p>
                                )}
                            </div>
                        )}
                        {can.edit && (
                            <Can permission="travel-orders.update">
                                <Button
                                    variant="outline"
                                    onClick={() => router.visit(urls.edit)}
                                >
                                    <Pencil className="size-4" />
                                    Edit
                                </Button>
                            </Can>
                        )}
                        {can.resubmit && (
                            <Can permission="travel-orders.resubmit">
                                <Button onClick={resubmit}>
                                    <Send className="size-4" />
                                    Resubmit for approval
                                </Button>
                            </Can>
                        )}
                        {can.submit && (
                            <Can permission="travel-orders.send">
                                <Button onClick={submit}>
                                    <Send className="size-4" />
                                    Submit for approval
                                </Button>
                            </Can>
                        )}
                        {can.delete && (
                            <Can permission="travel-orders.delete">
                                <Button variant="destructive" onClick={destroy}>
                                    <Trash2 className="size-4" />
                                    Delete
                                </Button>
                            </Can>
                        )}
                    </div>
                </div>

                <div className="space-y-4">
                    <TravelOrderDetails travelOrder={travelOrder} />

                    <TravelOrderItineraryPanel
                        travelOrder={travelOrder}
                        options={options}
                        canManage={can.manageItinerary}
                    />

                    <TravelOrderTripTicketPanel
                        travelOrder={travelOrder}
                        canCreate={can.createTripTicket}
                    />

                    <section className="space-y-3 rounded-xl border p-4">
                        <h3 className="text-base font-semibold">Audit trail</h3>
                        <ActionTimeline actions={timeline} />
                    </section>

                    <section className="space-y-3 rounded-xl border p-4">
                        <h3 className="text-base font-semibold">
                            Revision history
                        </h3>
                        <RevisionTimeline revisions={revisions} />
                    </section>
                </div>
            </div>
        </>
    );
}

ShowTravelOrder.layout = {
    breadcrumbs: [
        { title: 'Travel Order', href: travelOrdersRoutes.index().url },
    ],
};
