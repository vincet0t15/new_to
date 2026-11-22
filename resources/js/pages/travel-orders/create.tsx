import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    TravelOrderHeaderFields,
    type TravelOrderHeaderValues,
} from '@/components/travel-orders/header-fields';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
// native textarea used instead
import travelOrdersRoutes from '@/routes/travel-orders';
import type {
    TravelOrderCreateRequest,
    TravelOrderFormOptions,
    WorkflowPreview,
} from '@/types';

type Filer = {
    name: string;
    office?: string | null;
};

type Props = {
    options: TravelOrderFormOptions;
    initial: TravelOrderCreateRequest;
    routePreview: WorkflowPreview | null;
    filer: Filer | null;
};

export default function CreateTravelOrder({
    options,
    initial,
    routePreview,
    filer,
}: Props) {
    const { data, setData, post, processing, errors } =
        useForm<TravelOrderCreateRequest>(initial);

    const changeHeader = <K extends keyof TravelOrderHeaderValues>(
        field: K,
        value: TravelOrderHeaderValues[K],
    ) => {
        setData(field, value as never);
    };

    const submit = (status: 'draft' | 'submitted') => {
        setData('status', status);
        post(travelOrdersRoutes.store().url);
    };

    return (
        <>
            <Head title="New Travel Order" />
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
                        title="New Travel Order"
                        description="Fill in the details. The route is resolved automatically from your office."
                    />
                </div>

                {(errors as Record<string, string | undefined>).routing && (
                    <div className="rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                        {(errors as Record<string, string | undefined>).routing}
                    </div>
                )}
                {(errors as Record<string, string | undefined>).employee && (
                    <div className="rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                        {
                            (errors as Record<string, string | undefined>)
                                .employee
                        }
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {filer && (
                            <section className="space-y-1 rounded-xl border p-4">
                                <h3 className="text-base font-semibold">
                                    Filer
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {filer.name}
                                    {filer.office ? ` · ${filer.office}` : ''}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Travel orders are filed for your own
                                    employee record and office.
                                </p>
                            </section>
                        )}
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
                                    placeholder="Optional notes for the approvers"
                                />
                                <InputError message={errors.submission_notes} />
                            </div>
                        </section>
                    </div>

                    <div className="space-y-4">
                        <section className="space-y-3 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Approval route
                            </h3>
                            {routePreview ? (
                                <ol className="space-y-2">
                                    {routePreview.steps.map((step, index) => (
                                        <li
                                            key={step.id}
                                            className="flex items-start gap-2 text-sm"
                                        >
                                            <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                                                {index + 1}
                                            </span>
                                            <div>
                                                <p className="font-medium">
                                                    {step.label}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {step.office.office_name}
                                                    {step.designees.length > 0
                                                        ? ` · ${step.designees
                                                              .map(
                                                                  (d) => d.name,
                                                              )
                                                              .join(', ')}`
                                                        : ' · no designee'}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                    {routePreview.steps.length === 0 && (
                                        <p className="text-sm text-muted-foreground">
                                            No steps configured.
                                        </p>
                                    )}
                                </ol>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No route preview available.
                                </p>
                            )}
                            {routePreview && !routePreview.is_ready && (
                                <p className="text-sm text-amber-600">
                                    Some steps have no designee. Submission may
                                    be blocked.
                                </p>
                            )}
                        </section>

                        <div className="flex flex-col gap-2">
                            <Can permission="travel-orders.create">
                                <Button
                                    variant="outline"
                                    onClick={() => submit('draft')}
                                    disabled={processing}
                                >
                                    {processing && (
                                        <Loader2 className="size-4 animate-spin" />
                                    )}
                                    <Save className="size-4" />
                                    Save as draft
                                </Button>
                            </Can>
                            <Can permission="travel-orders.send">
                                <Button
                                    onClick={() => submit('submitted')}
                                    disabled={processing}
                                >
                                    {processing && (
                                        <Loader2 className="size-4 animate-spin" />
                                    )}
                                    Submit for approval
                                </Button>
                            </Can>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

CreateTravelOrder.layout = {
    breadcrumbs: [
        { title: 'New Travel Order', href: travelOrdersRoutes.create().url },
    ],
};
