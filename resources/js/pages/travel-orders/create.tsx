import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
// native textarea used instead
import travelOrdersRoutes from '@/routes/travel-orders';
import type {
    TravelOrderCreateRequest,
    TravelOrderFormOptions,
    WorkflowPreview,
} from '@/types';

type Props = {
    options: TravelOrderFormOptions;
    initial: TravelOrderCreateRequest;
    routePreview: WorkflowPreview | null;
};

export default function CreateTravelOrder({
    options,
    initial,
    routePreview,
}: Props) {
    const { data, setData, post, processing, errors } =
        useForm<TravelOrderCreateRequest>(initial);

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

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <section className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Basic information
                            </h3>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="travel_classification">
                                        Travel classification
                                    </Label>
                                    <Select
                                        value={data.travel_classification}
                                        onValueChange={(value) =>
                                            setData(
                                                'travel_classification',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="travel_classification">
                                            <SelectValue placeholder="Select classification" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.classifications.map(
                                                (item) => (
                                                    <SelectItem
                                                        key={item}
                                                        value={item}
                                                    >
                                                        {item}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.travel_classification}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="fund_source">
                                        Fund source
                                    </Label>
                                    <Select
                                        value={data.fund_source}
                                        onValueChange={(value) =>
                                            setData('fund_source', value)
                                        }
                                    >
                                        <SelectTrigger id="fund_source">
                                            <SelectValue placeholder="Select fund source" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.fundSources.map((item) => (
                                                <SelectItem
                                                    key={item}
                                                    value={item}
                                                >
                                                    {item}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.fund_source} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="purpose">Purpose *</Label>
                                <textarea
                                    id="purpose"
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.purpose}
                                    onChange={(event) =>
                                        setData('purpose', event.target.value)
                                    }
                                    placeholder="State the purpose of the travel"
                                />
                                <InputError message={errors.purpose} />
                            </div>
                        </section>

                        <section className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-base font-semibold">
                                Destination and dates
                            </h3>
                            <div className="space-y-2">
                                <Label htmlFor="destination">
                                    Destination *
                                </Label>
                                <Input
                                    id="destination"
                                    value={data.destination}
                                    onChange={(event) =>
                                        setData(
                                            'destination',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Where will the travel take place?"
                                />
                                <InputError message={errors.destination} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="date_from">
                                        Date from *
                                    </Label>
                                    <Input
                                        id="date_from"
                                        type="date"
                                        value={data.date_from}
                                        onChange={(event) =>
                                            setData(
                                                'date_from',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.date_from} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="date_to">Date to *</Label>
                                    <Input
                                        id="date_to"
                                        type="date"
                                        value={data.date_to}
                                        onChange={(event) =>
                                            setData(
                                                'date_to',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.date_to} />
                                </div>
                            </div>
                        </section>

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
