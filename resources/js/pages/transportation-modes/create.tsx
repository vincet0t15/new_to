import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { TravelSelect } from '@/components/travel-orders/travel-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import locationRoutes from '@/routes/locations';
import transportationModes from '@/routes/transportation-modes';
import type {
    TransportationFareInput,
    TransportationModeCreateRequest,
} from '@/types/transportation-mode';
import type { FormEvent } from 'react';

type CreateTransportationModeProps = {
    locations: { id: number; name: string }[];
};

/**
 * Create a transportation mode.
 *
 * A page of its own rather than a dialog over the list, because the fare
 * matrix is the substance of the form and a dialog large enough to hold it
 * scrolls inside the page behind it.
 */
export default function TransportationModesCreate({
    locations,
}: CreateTransportationModeProps) {
    const { data, setData, post, errors, processing } =
        useForm<TransportationModeCreateRequest>({
            name: '',
            description: '',
            fix_cost: '',
            is_government: false,
            is_active: true,
            fares: [],
        });

    const updateFare = (
        index: number,
        patch: Partial<TransportationFareInput>,
    ) => {
        setData(
            'fares',
            data.fares.map((fare, position) =>
                position === index ? { ...fare, ...patch } : fare,
            ),
        );
    };

    const removeFare = (index: number) => {
        setData(
            'fares',
            data.fares.filter((_, position) => position !== index),
        );
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(transportationModes.store().url);
    };

    return (
        <>
            <Head title="Create Transportation Mode" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Create Transportation Mode"
                    description="Add a new mode of transportation by providing the required details."
                />

                <Button
                    variant="ghost"
                    className="w-fit"
                    onClick={() => router.get(transportationModes.index().url)}
                >
                    <ArrowLeft />
                    Back to list
                </Button>

                <form onSubmit={handleSubmit} className="w-full space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">Name *</Label>
                        <Input
                            id="name"
                            name="name"
                            placeholder="e.g., Van"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            placeholder="e.g., Rental van for official travel"
                            rows={3}
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="fix_cost">Default Fare (₱)</Label>
                        <Input
                            id="fix_cost"
                            name="fix_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="e.g., 500.00"
                            value={data.fix_cost}
                            onChange={(event) =>
                                setData('fix_cost', event.target.value)
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            Fallback only. Used when the destination has no
                            specific fare below. Leave blank if every
                            destination has its own fare.
                        </p>
                        <InputError message={errors.fix_cost} />
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="is_government"
                            checked={data.is_government}
                            onCheckedChange={(checked) =>
                                setData('is_government', checked === true)
                            }
                        />
                        <Label htmlFor="is_government">
                            Government vehicle (no fare)
                        </Label>
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', checked === true)
                            }
                        />
                        <Label htmlFor="is_active">
                            Active (available for selection)
                        </Label>
                    </div>

                    <div className="space-y-3 border-t pt-4">
                        <div className="flex items-center justify-between">
                            <Label>Fare per Destination</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setData('fares', [
                                        ...data.fares,
                                        { location_id: '', amount: '' },
                                    ])
                                }
                            >
                                <Plus />
                                Add Fare
                            </Button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Each destination can have a different fare. A fare
                            here overrides the default fare above.
                        </p>

                        {locations.length === 0 && (
                            <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                No locations yet. Add them first so each
                                destination can have its own fare.{' '}
                                <a
                                    href={locationRoutes.index().url}
                                    className="font-medium underline underline-offset-4"
                                >
                                    Go to Locations
                                </a>
                            </div>
                        )}

                        {data.fares.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No per-destination fares yet.
                            </p>
                        )}

                        {data.fares.map((fare, index) => (
                            <div
                                key={index}
                                className="grid grid-cols-[1fr_110px_auto] items-end gap-2"
                            >
                                <TravelSelect
                                    id={`fare-location-${index}`}
                                    options={locations}
                                    value={fare.location_id}
                                    onChange={(value) =>
                                        updateFare(index, {
                                            location_id: value,
                                        })
                                    }
                                    placeholder="Select destination"
                                />
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="Fare"
                                    value={fare.amount}
                                    onChange={(event) =>
                                        updateFare(index, {
                                            amount: event.target.value,
                                        })
                                    }
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Remove fare ${index + 1}`}
                                    className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                    onClick={() => removeFare(index)}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        ))}
                    </div>

                    <div className="flex justify-end gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.get(transportationModes.index().url)
                            }
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Create Transportation Mode
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

TransportationModesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Transportation Modes',
            href: transportationModes.index().url,
        },
        {
            title: 'Create',
            href: transportationModes.create().url,
        },
    ],
};
