import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import travelOrdersRoutes from '@/routes/travel-orders';
import type { TravelOrder } from '@/types';

type Props = {
    travelOrder: TravelOrder;
};

export default function ShowTravelOrder({ travelOrder }: Props) {
    return (
        <>
            <Head title={`Travel Order #${travelOrder.id}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            router.visit(travelOrdersRoutes.index().url)
                        }
                    >
                        <ArrowLeft className="size-4" />
                        Back
                    </Button>
                    <Heading
                        title={`Travel Order #${travelOrder.id}`}
                        description="Details and routing."
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">Details</h3>
                            <dl className="grid gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Control No.
                                    </dt>
                                    <dd className="font-medium">
                                        {travelOrder.control_no ??
                                            travelOrder.to_number ??
                                            `#${travelOrder.id}`}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Status
                                    </dt>
                                    <dd>
                                        <Badge variant="outline">
                                            {travelOrder.status}
                                        </Badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Purpose
                                    </dt>
                                    <dd>{travelOrder.purpose ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Destination
                                    </dt>
                                    <dd>{travelOrder.destination ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Dates
                                    </dt>
                                    <dd>
                                        {travelOrder.date_from} →{' '}
                                        {travelOrder.date_to}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Classification
                                    </dt>
                                    <dd>
                                        {travelOrder.travel_classification ??
                                            '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Fund source
                                    </dt>
                                    <dd>{travelOrder.fund_source ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Office
                                    </dt>
                                    <dd>
                                        {travelOrder.office?.office_name ?? '—'}
                                    </dd>
                                </div>
                            </dl>
                            {travelOrder.submission_notes && (
                                <div className="mt-2">
                                    <p className="text-sm text-muted-foreground">
                                        Submission notes
                                    </p>
                                    <p className="text-sm">
                                        {travelOrder.submission_notes}
                                    </p>
                                </div>
                            )}
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}

ShowTravelOrder.layout = {
    breadcrumbs: [{ title: 'Travel Order', href: '#' }],
};
