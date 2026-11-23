import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileText, Loader2, Route, Save, Send } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
    const { data, setData, post, processing, errors, transform } =
        useForm<TravelOrderCreateRequest>(initial);

    const changeHeader = <K extends keyof TravelOrderHeaderValues>(
        field: K,
        value: TravelOrderHeaderValues[K],
    ) => {
        setData(field, value as never);
    };

    const submit = (status: 'draft' | 'submitted') => {
        transform((current) => ({ ...current, status }));
        post(travelOrdersRoutes.store().url, {
            preserveScroll: true,
        });
    };

    const routingError = (errors as Record<string, string | undefined>).routing;
    const employeeError = (errors as Record<string, string | undefined>).employee;

    return (
        <>
            <Head title="New Travel Order" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <Button variant="ghost" size="sm" className="-ml-2 w-fit" asChild>
                            <Link href={travelOrdersRoutes.index().url}>
                                <ArrowLeft className="size-4" />
                                Back to travel orders
                            </Link>
                        </Button>
                        <div className="space-y-1">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                New Travel Order
                            </h1>
                            <p className="max-w-2xl text-sm text-muted-foreground">
                                Complete the travel details below. The approval
                                route is resolved automatically from your office.
                            </p>
                        </div>
                    </div>
                    {filer && (
                        <div className="rounded-lg border bg-muted/30 px-4 py-3 text-sm sm:max-w-xs">
                            <p className="font-medium text-foreground">{filer.name}</p>
                            <p className="text-muted-foreground">
                                {filer.office ?? 'No office assigned'}
                            </p>
                        </div>
                    )}
                </div>

                {(routingError || employeeError) && (
                    <div
                        role="alert"
                        className="rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive"
                    >
                        {routingError ?? employeeError}
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
                                    Optional context for approvers reviewing this
                                    request.
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
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Route className="size-4 text-muted-foreground" />
                                    Approval route
                                </CardTitle>
                                <CardDescription>
                                    Steps are determined by your office workflow.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {routePreview ? (
                                    <>
                                        <ol className="space-y-3">
                                            {routePreview.steps.map((step, index) => (
                                                <li
                                                    key={step.id}
                                                    className="flex gap-3 text-sm"
                                                >
                                                    <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border bg-background text-xs font-medium text-foreground">
                                                        {index + 1}
                                                    </span>
                                                    <div className="min-w-0">
                                                        <p className="font-medium leading-6 text-foreground">
                                                            {step.label}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {step.office.office_name}
                                                            {step.designees.length > 0
                                                                ? ` · ${step.designees
                                                                      .map((d) => d.name)
                                                                      .join(', ')}`
                                                                : ' · No designee assigned'}
                                                        </p>
                                                    </div>
                                                </li>
                                            ))}
                                            {routePreview.steps.length === 0 && (
                                                <li className="text-sm text-muted-foreground">
                                                    No workflow steps are configured.
                                                </li>
                                            )}
                                        </ol>
                                        {!routePreview.is_ready && (
                                            <p className="rounded-md border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-xs text-amber-700 dark:text-amber-400">
                                                Some steps have no designee. Submission
                                                may be blocked until the route is ready.
                                            </p>
                                        )}
                                    </>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No route preview available for your account.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Actions</CardTitle>
                                <CardDescription>
                                    Save your progress or submit when ready.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                <Can permission="travel-orders.send">
                                    <Button
                                        className="w-full"
                                        onClick={() => submit('submitted')}
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <Loader2 className="size-4 animate-spin" />
                                        ) : (
                                            <Send className="size-4" />
                                        )}
                                        Submit for approval
                                    </Button>
                                </Can>
                                <Can permission="travel-orders.create">
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        onClick={() => submit('draft')}
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <Loader2 className="size-4 animate-spin" />
                                        ) : (
                                            <Save className="size-4" />
                                        )}
                                        Save as draft
                                    </Button>
                                </Can>
                            </CardContent>
                        </Card>
                    </aside>
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
