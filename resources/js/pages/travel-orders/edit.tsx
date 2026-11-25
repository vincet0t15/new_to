import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, FileText, Loader2, Save, Send } from 'lucide-react';
import { useState } from 'react';
import { Can } from '@/components/can';
import InputError from '@/components/input-error';
import {
    TravelOrderHeaderFields,
    type TravelOrderHeaderValues,
} from '@/components/travel-orders/header-fields';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
    const [confirmUnsaved, setConfirmUnsaved] = useState(false);
    const { data, setData, put, processing, errors, isDirty } = useForm({
        travel_classification_id: toIdInput(
            travelOrder.travel_classification_id,
        ),
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

    const fileForApproval = () => {
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

    const saveAndFile = () => {
        setConfirmUnsaved(false);
        setSubmitting(true);
        put(travelOrdersRoutes.update(travelOrder).url, {
            preserveScroll: true,
            onSuccess: () => fileForApproval(),
            onError: () => setSubmitting(false),
        });
    };

    const submitForApproval = () => {
        if (isDirty) {
            setConfirmUnsaved(true);
            return;
        }

        fileForApproval();
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

    const routingError = (errors as Record<string, string | undefined>).routing;

    return (
        <>
            <Head title="Edit Travel Order" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            className="-ml-2 w-fit"
                            asChild
                        >
                            <Link
                                href={travelOrdersRoutes.show(travelOrder).url}
                            >
                                <ArrowLeft className="size-4" />
                                Back to travel order
                            </Link>
                        </Button>
                        <div className="space-y-1">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                Edit Travel Order
                            </h1>
                            <p className="max-w-2xl text-sm text-muted-foreground">
                                Update the travel details before saving or
                                resubmitting the request.
                            </p>
                        </div>
                    </div>
                    <div className="rounded-lg border bg-muted/30 px-4 py-3 text-sm sm:max-w-xs">
                        <p className="font-medium text-foreground">
                            TO #{travelOrder.id}
                        </p>
                        <p className="text-muted-foreground">
                            Revision {travelOrder.revision_number}
                        </p>
                    </div>
                </div>

                {travelOrder.status === 'returned' && (
                    <div
                        role="status"
                        className="rounded-lg border border-amber-500/40 bg-amber-500/5 p-4 text-sm text-amber-900 dark:text-amber-300"
                    >
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
                            <p className="mt-2 whitespace-pre-wrap text-amber-800 dark:text-amber-400">
                                {travelOrder.return_remarks}
                            </p>
                        )}
                        <p className="mt-2 text-xs text-amber-700 dark:text-amber-400">
                            Saving keeps this travel order returned. Use
                            Resubmit to file it again.
                        </p>
                    </div>
                )}

                {routingError && (
                    <div
                        role="alert"
                        className="rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive"
                    >
                        {routingError}
                    </div>
                )}

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <div className="min-w-0 space-y-6">
                        <TravelOrderHeaderFields
                            options={options}
                            data={data}
                            errors={errors}
                            onChange={changeHeader}
                        />

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <FileText className="size-4 text-muted-foreground" />
                                    Additional notes
                                </CardTitle>
                                <CardDescription>
                                    Optional context for approvers reviewing
                                    this request.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <Label htmlFor="submission_notes">
                                    Submission notes
                                </Label>
                                <Textarea
                                    id="submission_notes"
                                    rows={3}
                                    value={data.submission_notes}
                                    onChange={(event) =>
                                        setData(
                                            'submission_notes',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Add any note that may help approvers"
                                />
                                <InputError message={errors.submission_notes} />
                            </CardContent>
                        </Card>
                    </div>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Actions
                                </CardTitle>
                                <CardDescription>
                                    Save changes, submit, or resubmit this
                                    travel order.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                <Can permission="travel-orders.update">
                                    <Button
                                        className="w-full"
                                        onClick={submit}
                                        disabled={
                                            processing ||
                                            submitting ||
                                            resubmitting
                                        }
                                    >
                                        {processing ? (
                                            <Loader2 className="size-4 animate-spin" />
                                        ) : (
                                            <Save className="size-4" />
                                        )}
                                        Save changes
                                    </Button>
                                </Can>
                                {canSubmit && (
                                    <Can permission="travel-orders.send">
                                        <Button
                                            variant="outline"
                                            className="w-full"
                                            onClick={submitForApproval}
                                            disabled={
                                                processing ||
                                                submitting ||
                                                resubmitting
                                            }
                                        >
                                            {submitting ? (
                                                <Loader2 className="size-4 animate-spin" />
                                            ) : (
                                                <Send className="size-4" />
                                            )}
                                            Submit for approval
                                        </Button>
                                    </Can>
                                )}
                                {canResubmit && (
                                    <Can permission="travel-orders.resubmit">
                                        <div className="space-y-3 rounded-lg border p-3">
                                            <div className="space-y-2">
                                                <Label htmlFor="revision_remarks">
                                                    Resubmission remarks{' '}
                                                    <span className="text-destructive">
                                                        *
                                                    </span>
                                                </Label>
                                                <Textarea
                                                    id="revision_remarks"
                                                    rows={3}
                                                    value={
                                                        data.revision_remarks
                                                    }
                                                    onChange={(event) =>
                                                        setData(
                                                            'revision_remarks',
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="Describe what changed"
                                                />
                                                <InputError
                                                    message={
                                                        errors.revision_remarks
                                                    }
                                                />
                                            </div>
                                            <Button
                                                className="w-full"
                                                onClick={resubmitForApproval}
                                                disabled={
                                                    processing ||
                                                    submitting ||
                                                    resubmitting
                                                }
                                            >
                                                {resubmitting ? (
                                                    <Loader2 className="size-4 animate-spin" />
                                                ) : (
                                                    <Send className="size-4" />
                                                )}
                                                Resubmit for approval
                                            </Button>
                                        </div>
                                    </Can>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Revision history
                                </CardTitle>
                                <CardDescription>
                                    Previous returns and resubmissions.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <RevisionTimeline revisions={revisions} />
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </div>

            <Dialog open={confirmUnsaved} onOpenChange={setConfirmUnsaved}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Unsaved changes</DialogTitle>
                        <DialogDescription>
                            You changed this travel order but have not saved it.
                            Submission files the saved version, so your edits
                            would be left out.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setConfirmUnsaved(false)}
                        >
                            Keep editing
                        </Button>
                        <Button
                            type="button"
                            onClick={saveAndFile}
                            disabled={submitting}
                        >
                            {submitting ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <Save className="size-4" />
                            )}
                            Save and submit
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

EditTravelOrder.layout = {
    breadcrumbs: [{ title: 'Edit Travel Order', href: '#' }],
};
