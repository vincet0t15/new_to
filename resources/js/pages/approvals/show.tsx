import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CornerUpRight,
    Inbox,
    RotateCcw,
} from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { ActionTimeline } from '@/components/travel-orders/action-timeline';
import { RouteProgress } from '@/components/travel-orders/route-progress';
import { DeliveryStatusBadge } from '@/components/travel-orders/status-badge';
import { TravelOrderDetails } from '@/components/travel-orders/travel-order-details';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type {
    ApprovalAbilities,
    TravelOrder,
    TravelOrderDeliveryStatus,
    TravelOrderRouteStep,
    TravelOrderTimelineEntry,
} from '@/types';

type Props = {
    travelOrder: TravelOrder;
    route: TravelOrderRouteStep[];
    timeline: TravelOrderTimelineEntry[];
    currentStep: {
        label: string;
        office: string | null;
        is_signatory: boolean;
    } | null;
    myDeliveryStatus: TravelOrderDeliveryStatus | null;
    forwardCandidates: { id: number; name: string }[];
    can: ApprovalAbilities;
    urls: {
        inbox: string;
        receive: string;
        approve: string;
        acknowledge: string;
        return: string;
        forward: string;
    };
};

export default function ShowApproval({
    travelOrder,
    route,
    timeline,
    currentStep,
    myDeliveryStatus,
    forwardCandidates,
    can,
    urls,
}: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        remarks: '',
        to_user_id: '',
    });

    const actionError = (errors as Record<string, string | undefined>).action;

    const act = (url: string) => {
        post(url, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const hasActions =
        can.receive ||
        can.approve ||
        can.acknowledge ||
        can.return ||
        can.forward;

    return (
        <>
            <Head title={`Travel Order #${travelOrder.id}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => router.visit(urls.inbox)}
                    >
                        <ArrowLeft className="size-4" />
                        Inbox
                    </Button>
                    <Heading
                        title={`Travel Order #${travelOrder.id}`}
                        description="Review the details, act on it, or hand it to a colleague at this step."
                    />
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
                            <div className="flex items-center gap-2">
                                <Inbox className="size-4" />
                                <h3 className="text-base font-semibold">
                                    Your action
                                </h3>
                            </div>

                            {currentStep && (
                                <div className="rounded-lg bg-muted p-3 text-sm">
                                    <p className="font-medium">
                                        {currentStep.label}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {currentStep.office}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {currentStep.is_signatory
                                            ? 'This step records an approval.'
                                            : 'This step records a receipt.'}
                                    </p>
                                    {myDeliveryStatus && (
                                        <div className="mt-2">
                                            <DeliveryStatusBadge
                                                status={myDeliveryStatus}
                                            />
                                        </div>
                                    )}
                                </div>
                            )}

                            {actionError && (
                                <div className="rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                                    {actionError}
                                </div>
                            )}

                            {hasActions ? (
                                <div className="space-y-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="remarks">Remarks</Label>
                                        <textarea
                                            id="remarks"
                                            rows={3}
                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            value={data.remarks}
                                            onChange={(event) =>
                                                setData(
                                                    'remarks',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Optional for approve or forward; required when returning."
                                        />
                                        <InputError message={errors.remarks} />
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        {can.receive && (
                                            <Button
                                                variant="outline"
                                                disabled={processing}
                                                onClick={() =>
                                                    router.post(
                                                        urls.receive,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Check className="size-4" />
                                                Receive
                                            </Button>
                                        )}
                                        {can.approve && (
                                            <Button
                                                disabled={processing}
                                                onClick={() =>
                                                    act(urls.approve)
                                                }
                                            >
                                                <Check className="size-4" />
                                                Approve
                                            </Button>
                                        )}
                                        {can.acknowledge && (
                                            <Button
                                                disabled={processing}
                                                onClick={() =>
                                                    act(urls.acknowledge)
                                                }
                                            >
                                                <Check className="size-4" />
                                                Acknowledge
                                            </Button>
                                        )}
                                        {can.return && (
                                            <Button
                                                variant="outline"
                                                disabled={processing}
                                                onClick={() => act(urls.return)}
                                            >
                                                <RotateCcw className="size-4" />
                                                Return
                                            </Button>
                                        )}
                                    </div>

                                    {can.forward && (
                                        <div className="space-y-2 border-t pt-3">
                                            <Label htmlFor="to_user_id">
                                                Forward to
                                            </Label>
                                            <select
                                                id="to_user_id"
                                                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                                value={data.to_user_id}
                                                onChange={(event) =>
                                                    setData(
                                                        'to_user_id',
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Choose a person
                                                </option>
                                                {forwardCandidates.map(
                                                    (candidate) => (
                                                        <option
                                                            key={candidate.id}
                                                            value={candidate.id}
                                                        >
                                                            {candidate.name}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={errors.to_user_id}
                                            />
                                            <Button
                                                variant="secondary"
                                                disabled={processing}
                                                onClick={() =>
                                                    act(urls.forward)
                                                }
                                            >
                                                <CornerUpRight className="size-4" />
                                                Forward
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    This travel order is not waiting for your
                                    action.
                                </p>
                            )}
                        </section>

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

ShowApproval.layout = {
    breadcrumbs: [{ title: 'Approval Inbox', href: '#' }],
};
