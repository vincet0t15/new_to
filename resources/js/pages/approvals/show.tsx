import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CornerUpRight,
    Inbox,
    RotateCcw,
} from 'lucide-react';
import { useState, type KeyboardEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { ActionTimeline } from '@/components/travel-orders/action-timeline';
import { RevisionTimeline } from '@/components/travel-orders/revision-timeline';
import { DeliveryStatusBadge } from '@/components/travel-orders/status-badge';
import { ItineraryDocument } from '@/components/travel-orders/print/itinerary-document';
import { TravelOrderDocument } from '@/components/travel-orders/print/travel-order-document';
import { TripTicketDocument } from '@/components/travel-orders/print/trip-ticket-document';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { APPROVAL_INBOX_LABEL } from '@/lib/approval-inbox-label';
import type {
    ApprovalAbilities,
    ItinerarySignatories,
    Letterhead,
    TravelOrder,
    TravelOrderDeliveryStatus,
    TravelOrderRevision,
    TravelOrderRouteStep,
    TravelOrderSignatories,
    TravelOrderTimelineEntry,
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
    route: TravelOrderRouteStep[];
    timeline: TravelOrderTimelineEntry[];
    revisions: TravelOrderRevision[];
    currentStep: {
        label: string;
        office: string | null;
        is_signatory: boolean;
    } | null;
    myDeliveryStatus: TravelOrderDeliveryStatus | null;
    forwardCandidates: { id: number; name: string }[];
    fundSources: { id: number; name: string }[];
    letterhead: Letterhead;
    signatories: TravelOrderSignatories;
    itinerarySignatories: ItinerarySignatories;
    tripTickets: TripTicket[];
    can: ApprovalAbilities;
    urls: {
        inbox: string;
        receive: string;
        approve: string;
        acknowledge: string;
        return: string;
        forward: string;
        earmark: string;
        assignFundSource: string;
        record: string;
    };
};

export default function ShowApproval({
    travelOrder,
    route: _route,
    timeline,
    revisions,
    currentStep,
    myDeliveryStatus,
    forwardCandidates,
    fundSources,
    letterhead,
    signatories,
    itinerarySignatories,
    tripTickets,
    can,
    urls,
}: Props) {
    const [documentTab, setDocumentTab] = useState<DocumentTab>('order');

    const selectTab = (key: DocumentTab) => {
        setDocumentTab(key);
        document.getElementById(`document-tab-${key}`)?.focus();
    };

    const onTabKeyDown = (
        event: KeyboardEvent<HTMLButtonElement>,
        key: DocumentTab,
    ) => {
        const currentIndex = documentTabs.findIndex(
            ([tabKey]) => tabKey === key,
        );
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
        selectTab(documentTabs[nextIndex][0]);
    };
    const { data, setData, post, processing, errors, reset } = useForm({
        remarks: '',
        to_user_id: '',
        control_no: travelOrder.control_no ?? '',
        to_number: travelOrder.to_number ?? '',
        distributed: travelOrder.distributed_at !== null,
        fund_source_id: travelOrder.fund_source_id?.toString() ?? '',
        earmark_number: travelOrder.earmark_number ?? '',
    });

    const actionError = (errors as Record<string, string | undefined>).action;

    const act = (url: string) => {
        post(url, {
            preserveScroll: true,
            onSuccess: () => reset('remarks', 'to_user_id', 'distributed'),
        });
    };

    const hasActions =
        can.receive ||
        can.approve ||
        can.acknowledge ||
        can.return ||
        can.forward ||
        can.earmark ||
        can.assignFundSource ||
        can.record;

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
                                        onKeyDown={(event) =>
                                            onTabKeyDown(event, key)
                                        }
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
                                        itinerarySignatories={
                                            itinerarySignatories
                                        }
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
                                        No trip ticket has been issued for this
                                        travel order yet.
                                    </div>
                                )}
                            </div>
                        </section>

                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Audit trail
                            </h3>
                            <ActionTimeline actions={timeline} />
                        </section>

                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Revision history
                            </h3>
                            <RevisionTimeline revisions={revisions} />
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
                                    {(travelOrder.earmarked_at ||
                                        travelOrder.recorded_at ||
                                        travelOrder.fund_source_assigned_at) && (
                                        <div className="space-y-1 rounded-lg border p-3 text-xs text-muted-foreground">
                                            {travelOrder.fund_source_assigned_at && (
                                                <p>
                                                    Fund source assigned:{' '}
                                                    {
                                                        travelOrder.fund_source_assigned_at
                                                    }
                                                </p>
                                            )}
                                            {travelOrder.earmarked_at && (
                                                <p>
                                                    Earmarked:{' '}
                                                    {travelOrder.earmarked_at}
                                                    {travelOrder.earmark_number
                                                        ? ` · No. ${travelOrder.earmark_number}`
                                                        : ''}
                                                </p>
                                            )}
                                            {travelOrder.recorded_at && (
                                                <p>
                                                    Recorded:{' '}
                                                    {travelOrder.recorded_at}
                                                    {travelOrder.distributed_at
                                                        ? ' · Distributed'
                                                        : ''}
                                                </p>
                                            )}
                                        </div>
                                    )}

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
                                        {can.earmark && (
                                            <div className="w-full space-y-2 rounded-md border p-3">
                                                <p className="text-sm font-medium">
                                                    Budget earmark
                                                </p>
                                                <Label htmlFor="earmark_number">
                                                    Earmark number
                                                </Label>
                                                <input
                                                    id="earmark_number"
                                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                                    value={data.earmark_number}
                                                    onChange={(event) =>
                                                        setData(
                                                            'earmark_number',
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="Enter earmark number"
                                                />
                                                <InputError
                                                    message={
                                                        errors.earmark_number
                                                    }
                                                />
                                                <Button
                                                    variant="secondary"
                                                    disabled={processing}
                                                    onClick={() =>
                                                        act(urls.earmark)
                                                    }
                                                >
                                                    <Check className="size-4" />
                                                    Record Earmark
                                                </Button>
                                            </div>
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

                                    {can.assignFundSource && (
                                        <div className="space-y-2 border-t pt-3">
                                            <p className="text-sm font-medium">
                                                Fund source
                                            </p>
                                            <Label htmlFor="fund_source_id">
                                                Assign fund source
                                            </Label>
                                            <select
                                                id="fund_source_id"
                                                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                                value={data.fund_source_id}
                                                onChange={(event) =>
                                                    setData(
                                                        'fund_source_id',
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Select fund source
                                                </option>
                                                {fundSources.map(
                                                    (fundSource) => (
                                                        <option
                                                            key={fundSource.id}
                                                            value={
                                                                fundSource.id
                                                            }
                                                        >
                                                            {fundSource.name}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={errors.fund_source_id}
                                            />
                                            <Button
                                                variant="secondary"
                                                disabled={processing}
                                                onClick={() =>
                                                    act(urls.assignFundSource)
                                                }
                                            >
                                                <Check className="size-4" />
                                                Assign Fund Source
                                            </Button>
                                        </div>
                                    )}

                                    {can.record && (
                                        <div className="space-y-2 border-t pt-3">
                                            <p className="text-sm font-medium">
                                                HRMO recording
                                            </p>
                                            <div className="space-y-2">
                                                <Label htmlFor="to_number">
                                                    Series/TO No.
                                                </Label>
                                                <input
                                                    id="to_number"
                                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                                    value={data.to_number}
                                                    onChange={(e) =>
                                                        setData(
                                                            'to_number',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors.to_number}
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="control_no">
                                                    Control No.
                                                </Label>
                                                <input
                                                    id="control_no"
                                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                                    value={data.control_no}
                                                    onChange={(e) =>
                                                        setData(
                                                            'control_no',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors.control_no}
                                                />
                                            </div>
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={data.distributed}
                                                    onChange={(e) =>
                                                        setData(
                                                            'distributed',
                                                            e.target.checked,
                                                        )
                                                    }
                                                />
                                                Mark as distributed/released
                                            </label>
                                            <Button
                                                variant="secondary"
                                                disabled={processing}
                                                onClick={() => act(urls.record)}
                                            >
                                                <Check className="size-4" />{' '}
                                                Record &amp; Release
                                            </Button>
                                        </div>
                                    )}

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
                    </div>
                </div>
            </div>
        </>
    );
}

ShowApproval.layout = {
    breadcrumbs: [{ title: APPROVAL_INBOX_LABEL, href: '#' }],
};
