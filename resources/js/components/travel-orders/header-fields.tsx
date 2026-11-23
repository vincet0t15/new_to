import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TravelOrderFormOptions, TravelOrderLookupOption } from '@/types';

/**
 * The header fields shared by the create and edit screens.
 *
 * Keeping them in one component means a field added to the travel order
 * header cannot appear on one screen and quietly disappear from the other.
 */
export type TravelOrderHeaderValues = {
    travel_classification_id: string;
    origin_id: string;
    destination_id: string;
    official_station_id: string;
    purpose: string;
    date_from: string;
    date_to: string;
    other_expenses: string;
    expense_type_ids: string[];
    transportation_mode_ids: string[];
};

type Props = {
    options: TravelOrderFormOptions;
    data: TravelOrderHeaderValues;
    errors: Partial<Record<keyof TravelOrderHeaderValues, string>>;
    onChange: <K extends keyof TravelOrderHeaderValues>(
        field: K,
        value: TravelOrderHeaderValues[K],
    ) => void;
};

function lookupChoices(
    items: TravelOrderLookupOption[],
    selected: string | string[],
) {
    const selectedIds = Array.isArray(selected) ? selected : [selected];

    return items
        .filter(
            (item) =>
                item.is_active !== false ||
                selectedIds.includes(String(item.id)),
        )
        .map((item) => ({ value: String(item.id), label: item.name }));
}

export function TravelOrderHeaderFields({
    options,
    data,
    errors,
    onChange,
}: Props) {
    const others = options.expenseTypes.find((item) => item.name === 'Others');
    const othersSelected =
        others !== undefined &&
        data.expense_type_ids.includes(String(others.id));

    const toggleSelection = (
        field: 'expense_type_ids' | 'transportation_mode_ids',
        id: string,
        checked: boolean,
    ) => {
        const current = data[field];

        onChange(
            field,
            checked
                ? [...current, id]
                : current.filter((entry) => entry !== id),
        );

        if (
            field === 'expense_type_ids' &&
            !checked &&
            others !== undefined &&
            id === String(others.id)
        ) {
            onChange('other_expenses', '');
        }
    };

    return (
        <>
            <section className="space-y-4 rounded-xl border p-4">
                <h3 className="text-base font-semibold">Basic information</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="travel_classification_id">
                            Travel classification *
                        </Label>
                        <Combobox
                            id="travel_classification_id"
                            value={data.travel_classification_id}
                            onValueChange={(value) =>
                                onChange('travel_classification_id', value)
                            }
                            options={lookupChoices(
                                options.classifications,
                                data.travel_classification_id,
                            )}
                            placeholder="Select classification"
                            searchPlaceholder="Search classification..."
                            emptyMessage="No classifications found. Add one in Travel Setup."
                        />
                        <InputError message={errors.travel_classification_id} />
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
                            onChange('purpose', event.target.value)
                        }
                        placeholder="State the purpose of the travel"
                    />
                    <InputError message={errors.purpose} />
                </div>
            </section>

            <section className="space-y-4 rounded-xl border p-4">
                <h3 className="text-base font-semibold">Route and dates</h3>
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="origin_id">Origin</Label>
                        <Combobox
                            id="origin_id"
                            value={data.origin_id}
                            onValueChange={(value) =>
                                onChange('origin_id', value)
                            }
                            options={lookupChoices(
                                options.locations,
                                data.origin_id,
                            )}
                            placeholder="Select origin"
                            searchPlaceholder="Search location..."
                            emptyMessage="No locations found. Add them in Travel Setup."
                        />
                        <InputError message={errors.origin_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="destination_id">Destination *</Label>
                        <Combobox
                            id="destination_id"
                            value={data.destination_id}
                            onValueChange={(value) =>
                                onChange('destination_id', value)
                            }
                            options={lookupChoices(
                                options.locations,
                                data.destination_id,
                            )}
                            placeholder="Select destination"
                            searchPlaceholder="Search location..."
                            emptyMessage="No locations found. Add them in Travel Setup."
                        />
                        <InputError message={errors.destination_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="official_station_id">
                            Official station
                        </Label>
                        <Combobox
                            id="official_station_id"
                            value={data.official_station_id}
                            onValueChange={(value) =>
                                onChange('official_station_id', value)
                            }
                            options={lookupChoices(
                                options.locations,
                                data.official_station_id,
                            )}
                            placeholder="Select official station"
                            searchPlaceholder="Search location..."
                            emptyMessage="No locations found. Add them in Travel Setup."
                        />
                        <InputError message={errors.official_station_id} />
                    </div>
                </div>

                {options.locations.length === 0 && (
                    <p className="text-sm text-amber-600">
                        No locations are configured yet. Ask an administrator to
                        add them in Travel Setup before filing a travel order.
                    </p>
                )}

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="date_from">Date from *</Label>
                        <Input
                            id="date_from"
                            type="date"
                            value={data.date_from}
                            onChange={(event) =>
                                onChange('date_from', event.target.value)
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
                                onChange('date_to', event.target.value)
                            }
                        />
                        <InputError message={errors.date_to} />
                    </div>
                </div>
            </section>

            <section className="space-y-4 rounded-xl border p-4">
                <div>
                    <h3 className="text-base font-semibold">
                        Allowed mode(s) of transportation
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        Select every mode the travel may use.
                    </p>
                </div>
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    {options.transportationModes.map((mode) => {
                        const id = String(mode.id);
                        const checked =
                            data.transportation_mode_ids.includes(id);
                        const disabled = mode.is_active === false && !checked;

                        return (
                            <label
                                key={mode.id}
                                className="flex items-center gap-2 rounded-sm border p-2 text-sm"
                            >
                                <Checkbox
                                    checked={checked}
                                    disabled={disabled}
                                    onCheckedChange={(value) =>
                                        toggleSelection(
                                            'transportation_mode_ids',
                                            id,
                                            value === true,
                                        )
                                    }
                                />
                                {mode.name}
                            </label>
                        );
                    })}
                    {options.transportationModes.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No transportation modes configured. Add them in
                            Travel Setup.
                        </p>
                    )}
                </div>
                <InputError message={errors.transportation_mode_ids} />
            </section>

            <section className="space-y-4 rounded-xl border p-4">
                <div>
                    <h3 className="text-base font-semibold">
                        Expenses authorized
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        Select the expenses this travel order authorizes.
                    </p>
                </div>
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    {options.expenseTypes.map((expenseType) => {
                        const id = String(expenseType.id);
                        const checked = data.expense_type_ids.includes(id);
                        const disabled =
                            expenseType.is_active === false && !checked;

                        return (
                            <label
                                key={expenseType.id}
                                className="flex items-center gap-2 rounded-sm border p-2 text-sm"
                            >
                                <Checkbox
                                    checked={checked}
                                    disabled={disabled}
                                    onCheckedChange={(value) =>
                                        toggleSelection(
                                            'expense_type_ids',
                                            id,
                                            value === true,
                                        )
                                    }
                                />
                                {expenseType.name}
                            </label>
                        );
                    })}
                    {options.expenseTypes.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No expense types configured. Add them in Travel
                            Setup.
                        </p>
                    )}
                </div>
                {othersSelected && (
                    <div className="space-y-2">
                        <Label htmlFor="other_expenses">
                            Specify other expenses *
                        </Label>
                        <Input
                            id="other_expenses"
                            value={data.other_expenses}
                            onChange={(event) =>
                                onChange('other_expenses', event.target.value)
                            }
                            placeholder="Describe the other expenses"
                        />
                        <InputError message={errors.other_expenses} />
                    </div>
                )}
                <InputError message={errors.expense_type_ids} />
            </section>
        </>
    );
}
