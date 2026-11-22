import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, Send } from 'lucide-react';
import { useState } from 'react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    TravelOrderHeaderFields,
    type TravelOrderHeaderValues,
} from '@/components/travel-orders/header-fields';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { RevisionTimeline } from '@/components/travel-orders/revision-timeline';
import { formatDateTime } from '@/lib/date-format';
import travelOrdersRoutes from '@/routes/travel-orders';
import type {
    TravelOrder,
    TravelOrderFormOptions,
    TravelOrderRevision,
} from '@/types';

type Props = {
    travelOrder: TravelOrder;
    options: TravelOrderFormOptions;
    canSubmit: boolean;
    canResubmit: boolean;
    submitUrl: string;
    resubmitUrl: string;
    revisions: TravelOrderRevision[];
};

const toDateInput = (value: string | null): string =>
    value ? value.slice(0, 10) : '';

const toIdInput = (value: number | null): string =>
    value === null ? '' : String(value);

export default function EditTravelOrder({
    travelOrder,
    options,
    canSubmit,
    canResubmit,
    submitUrl,
    resubmitUrl,
    revisions,
}: Props) {
    const [submitting, setSubmitting] = useState(false);
    const [resubmitting, setResubmitting] = useState(false);
    const { data, setData, put, processing, errors } = useForm({
        travel_classification_id: toIdInput(
            travelOrder.travel_classification_id,
        ),
        fund_source_id: toIdInput(travelOrder.fund_source_id),
        origin_id: toIdInput(travelOrder.origin_id),
        destination_id: toIdInput(travelOrder.destination_id),
        official_station_id: toIdInput(travelOrder.official_station_id),
        purpose: travelOrder.purpose ?? '',
        date_from: toDateInput(travelOrder.date_from),
        date_to: toDateInput(travelOrder.date_to),
        submission_notes: travelOrder.submission_notes ?? '',
        other_expenses: travelOrder.other_expenses ?? '',
        expense_type_ids: (travelOrder.expense_types ?? []).map((item) =>
            String(item.id),
        ),
        transportation_mode_ids: (travelOrder.transportation_modes ?? []).map(
            (item) => String(item.id),
        ),
        revision_remarks: '',
    });

    const changeHeader = <K extends keyof TravelOrderHeaderValues>(
        field: K,
        value: TravelOrderHeaderValues[K],
    ) => {
        setData(field, value as never);
    };

    const submit = () => {
        put(travelOrdersRoutes.update(travelOrder).url);
    };

    const submitForApproval = () => {
        setSubmitting(true);
        router.post(
            submitUrl,
            {},
            {
                preserveScroll: true,
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const resubmitForApproval = () => {
        setResubmitting(true);
        router.post(
            resubmitUrl,
            {
                ...data,
                revision_remarks: data.revision_remarks,
            },
            {
                preserveScroll: true,
                onFinish: () => setResubmitting(false),
            },
        );
    };

    return (
        <>
            <Head title="Edit Travel Order" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            router.visit(
                                travelOrdersRoutes.show(travelOrder).url,
                            )
                        }
                    >
                        <ArrowLeft className="size-4" />
                        Back
                    </Button>
                    <Heading
                        title="Edit Travel Order"
                        description="Update the travel order details."
                    />
                </div>

                {travelOrder.status === 'returned' && (
                    <div className="rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-800 dark:text-amber-300">
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
                        <p className="mt-1 text-xs">
                            Current revision {travelOrder.revision_number}.
                            Saving keeps this travel order returned; use
                            Resubmit to file it.
                        </p>
                    </div>
                )}

                {(errors as Record<string, string | undefined>).routing && (
                    <div className="rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                        {(errors as Record<string, string | undefined>).routing}
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <TravelOrderHeaderFields
                            options={options}
                            data={data}
                            errors={errors}
                            onChange={changeHeader}
                        />

                        <section className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Additional notes
                            </h3>
                            <div className="space-y-2">
                                <Label htmlFor="submission_notes">
                                    Submission notes
                                </Label>
                                <textarea
                                    id="submission_notes"
                                    rows={2}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.submission_notes}
                                    onChange={(event) =>
                                        setData(
                                            'submission_notes',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.submission_notes} />
                            </div>
                        </section>
                    </div>
                    <div className="space-y-6">
                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Revision history
                            </h3>
                            <RevisionTimeline revisions={revisions} />
                        </section>
                    </div>
                </div>

                <div className="flex justify-end gap-2">
                    <Can permission="travel-orders.update">
                        <Button onClick={submit} disabled={processing}>
                            {processing && (
                                <Loader2 className="size-4 animate-spin" />
                            )}
                            <Save className="size-4" />
                            Save changes
                        </Button>
                    </Can>
                    {canSubmit && (
                        <Can permission="travel-orders.send">
                            <Button
                                onClick={submitForApproval}
                                disabled={submitting}
                            >
                                {submitting && (
                                    <Loader2 className="size-4 animate-spin" />
                                )}
                                <Send className="size-4" />
                                Submit for approval
                            </Button>
                        </Can>
                    )}
                    {canResubmit && (
                        <Can permission="travel-orders.resubmit">
                            <div className="flex w-full flex-col gap-2 sm:w-auto">
                                <Label htmlFor="revision_remarks">
                                    Resubmission remarks *
                                </Label>
                                <textarea
                                    id="revision_remarks"
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.revision_remarks}
                                    onChange={(event) =>
                                        setData(
                                            'revision_remarks',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="What did you change?"
                                />
                                <InputError message={errors.revision_remarks} />
                                <Button
                                    onClick={resubmitForApproval}
                                    disabled={resubmitting}
                                >
                                    {resubmitting && (
                                        <Loader2 className="size-4 animate-spin" />
                                    )}
                                    <Send className="size-4" />
                                    Resubmit for approval
                                </Button>
                            </div>
                        </Can>
                    )}
                </div>
            </div>
        </>
    );
}

EditTravelOrder.layout = {
    breadcrumbs: [{ title: 'Edit Travel Order', href: '#' }],
};
