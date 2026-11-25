import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { DestinationsPicker } from '@/components/per-diem-configurations/destinations-picker';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import perDiemConfigurationRoutes from '@/routes/per-diem-configurations';
import type { PerDiemConfigurationCreateRequest } from '@/types/per-diem-configuration';
import type { FormEvent, ReactNode } from 'react';

type Option = { id: number; name: string };

type CreatePerDiemConfigurationProps = {
    locations: Option[];
};

function Field({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: ReactNode;
    error?: string;
    hint?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

/**
 * Create a per diem configuration.
 *
 * A page of its own rather than a dialog over the list. The destinations and
 * the effectivity are what somebody is here to set, and the destinations alone
 * are a multi-select that grows with the number of places an office travels to,
 * so the form is given the width of the screen instead of the width of a modal.
 */
export default function PerDiemConfigurationsCreate({
    locations,
}: CreatePerDiemConfigurationProps) {
    const { data, setData, post, errors, processing } =
        useForm<PerDiemConfigurationCreateRequest>({
            name: '',
            location_ids: [],
            effective_from: '',
            effective_until: '',
            is_active: true,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(perDiemConfigurationRoutes.store().url);
    };

    return (
        <>
            <Head title="Create Per Diem Configuration" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Create Per Diem Configuration"
                    description="Define the daily rate, meal breakdown and destination coverage of a per diem."
                />

                <Button
                    variant="ghost"
                    className="w-fit"
                    onClick={() =>
                        router.get(perDiemConfigurationRoutes.index().url)
                    }
                >
                    <ArrowLeft />
                    Back to list
                </Button>

                <form onSubmit={handleSubmit} className="w-full space-y-6">
                    <Field
                        id="name"
                        label="Configuration Name *"
                        error={errors.name}
                    >
                        <Input
                            id="name"
                            name="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                            autoFocus
                            placeholder="Enter name"
                        />
                    </Field>

                    <DestinationsPicker
                        destinations={locations}
                        value={data.location_ids}
                        onChange={(values) => setData('location_ids', values)}
                        error={errors.location_ids}
                    />

                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <Field
                            id="effective_from"
                            label="Effective From *"
                            error={errors.effective_from}
                            hint="New claims from this date use this configuration."
                        >
                            <Input
                                id="effective_from"
                                name="effective_from"
                                type="date"
                                max="9999-12-31"
                                value={data.effective_from}
                                onChange={(event) =>
                                    setData(
                                        'effective_from',
                                        event.target.value,
                                    )
                                }
                                required
                                placeholder="YYYY-MM-DD"
                            />
                        </Field>

                        <Field
                            id="effective_until"
                            label="Effective Until"
                            error={errors.effective_until}
                            hint="Leave blank if the configuration has no end date."
                        >
                            <Input
                                id="effective_until"
                                name="effective_until"
                                type="date"
                                max="9999-12-31"
                                value={data.effective_until}
                                onChange={(event) =>
                                    setData(
                                        'effective_until',
                                        event.target.value,
                                    )
                                }
                                placeholder="YYYY-MM-DD"
                            />
                        </Field>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', checked === true)
                            }
                        />
                        <Label htmlFor="is_active">
                            Active (available for claim computation)
                        </Label>
                    </div>

                    <div className="flex justify-end gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.get(
                                    perDiemConfigurationRoutes.index().url,
                                )
                            }
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Create Configuration
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PerDiemConfigurationsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Per Diem Configurations',
            href: perDiemConfigurationRoutes.index().url,
        },
        {
            title: 'Create',
            href: perDiemConfigurationRoutes.create().url,
        },
    ],
};
