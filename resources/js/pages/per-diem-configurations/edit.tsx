import { Head, Link, router, useForm } from '@inertiajs/react';
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
import type {
    PerDiemConfiguration,
    PerDiemConfigurationCreateRequest,
} from '@/types/per-diem-configuration';
import type { FormEvent, ReactNode } from 'react';

type Option = { id: number; name: string };

type EditPerDiemConfigurationProps = {
    perDiemConfiguration: PerDiemConfiguration;
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
 * Edit a per diem configuration.
 *
 * A page of its own for the same reason as creating one. The meal breakdown is
 * not on this screen — it is a list of its own under the configuration — so
 * what is here is the name, where the configuration applies and when, which is
 * the part that decides which orders ever read it.
 */
export default function PerDiemConfigurationsEdit({
    perDiemConfiguration,
    locations,
}: EditPerDiemConfigurationProps) {
    const { data, setData, put, errors, processing } =
        useForm<PerDiemConfigurationCreateRequest>({
            name: perDiemConfiguration.name,
            location_ids:
                perDiemConfiguration.locations?.map(
                    (location) => location.id,
                ) ?? [],
            effective_from: perDiemConfiguration.effective_from ?? '',
            effective_until: perDiemConfiguration.effective_until ?? '',
            is_active: perDiemConfiguration.is_active,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(perDiemConfigurationRoutes.update(perDiemConfiguration).url);
    };

    return (
        <>
            <Head title="Edit Per Diem Configuration" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Edit Per Diem Configuration"
                    description="Update where this per diem applies and when. The daily rate and its meals are managed separately."
                />

                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        variant="ghost"
                        onClick={() =>
                            router.get(perDiemConfigurationRoutes.index().url)
                        }
                    >
                        <ArrowLeft />
                        Back to list
                    </Button>

                    <Button variant="outline" asChild>
                        <Link
                            href={
                                perDiemConfigurationRoutes.manage(
                                    perDiemConfiguration,
                                ).url
                            }
                        >
                            Manage meal rules
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="w-full space-y-6">
                    <Field
                        id="name"
                        label="Configuration Name *"
                        error={errors.name}
                    >
                        <Input
                            id="name"
                            name="name"
                            placeholder="e.g., Regular Provincial Travel"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                            autoFocus
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
                                value={data.effective_from}
                                onChange={(event) =>
                                    setData(
                                        'effective_from',
                                        event.target.value,
                                    )
                                }
                                required
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
                                value={data.effective_until}
                                onChange={(event) =>
                                    setData(
                                        'effective_until',
                                        event.target.value,
                                    )
                                }
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
                            Save Changes
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PerDiemConfigurationsEdit.layout = {
    breadcrumbs: [
        {
            title: 'Per Diem Configurations',
            href: perDiemConfigurationRoutes.index().url,
        },
        {
            title: 'Edit',
            href: perDiemConfigurationRoutes.index().url,
        },
    ],
};
