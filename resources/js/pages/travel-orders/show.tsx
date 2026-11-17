import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Send, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { ActionTimeline } from '@/components/travel-orders/action-timeline';
import { RouteProgress } from '@/components/travel-orders/route-progress';
import { TravelOrderDetails } from '@/components/travel-orders/travel-order-details';
import { Button } from '@/components/ui/button';
import travelOrdersRoutes from '@/routes/travel-orders';
import type {
    TravelOrder,
    TravelOrderAbilities,
    TravelOrderRouteStep,
    TravelOrderTimelineEntry,
} from '@/types';

type Props = {
    travelOrder: TravelOrder;
    route: TravelOrderRouteStep[];
    timeline: TravelOrderTimelineEntry[];
    can: TravelOrderAbilities;
    urls: {
        index: string;
        edit: string;
        submit: string;
        destroy: string;
    };
};

export default function ShowTravelOrder({
    travelOrder,
    route,
    timeline,
    can,
    urls,
}: Props) {
    const submit = () => {
        router.post(urls.submit, {}, { preserveScroll: true });
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
                        description="Details, route progress, and audit trail."
                    />
                    <div className="ml-auto flex flex-wrap gap-2">
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

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <TravelOrderDetails travelOrder={travelOrder} />

                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Audit trail
                            </h3>
                            <ActionTimeline actions={timeline} />
                        </section>
                    </div>

                    <div className="space-y-4">
                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Approval route
                            </h3>
                            <RouteProgress steps={route} />
                        </section>
                    </div>
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
