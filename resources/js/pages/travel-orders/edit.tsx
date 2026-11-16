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
import travelOrdersRoutes from '@/routes/travel-orders';
import type { TravelOrder, TravelOrderFormOptions } from '@/types';

type Props = {
    travelOrder: TravelOrder;
    options: TravelOrderFormOptions;
};

export default function EditTravelOrder({ travelOrder, options }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: travelOrder.employee_id ?? '',
        office_id: travelOrder.office_id ?? '',
        travel_classification: travelOrder.travel_classification ?? '',
        fund_source: travelOrder.fund_source ?? '',
        purpose: travelOrder.purpose ?? '',
        destination: travelOrder.destination ?? '',
        date_from: travelOrder.date_from ?? '',
        date_to: travelOrder.date_to ?? '',
        submission_notes: travelOrder.submission_notes ?? '',
    });

    const submit = () => {
        put(travelOrdersRoutes.update(travelOrder).url);
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
                                />
                                <InputError message={errors.submission_notes} />
                            </div>
                        </section>
                    </div>
                </div>

                <div className="flex justify-end">
                    <Can permission="travel-orders.update">
                        <Button onClick={submit} disabled={processing}>
                            {processing && (
                                <Loader2 className="size-4 animate-spin" />
                            )}
                            <Save className="size-4" />
                            Save changes
                        </Button>
                    </Can>
                </div>
            </div>
        </>
    );
}

EditTravelOrder.layout = {
    breadcrumbs: [{ title: 'Edit Travel Order', href: '#' }],
};
