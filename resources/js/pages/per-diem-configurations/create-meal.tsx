import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { CustomComboBox } from '@/components/CustomComboBox';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import perDiemConfigurationRoutes from '@/routes/per-diem-configurations';
import type {
    MealRuleInput,
    MealTypeOption,
    PerDiemConfiguration,
} from '@/types/per-diem-configuration';
import type { FormEvent } from 'react';

type CreateMealRuleProps = {
    perDiemConfiguration: PerDiemConfiguration;
    mealTypes: MealTypeOption[];
    nextSortOrder: number;
};

/**
 * Add a meal rule to a per diem.
 *
 * A page of its own, like every other form in this module: the cutoff time
 * changes what the claim deducts, so it deserves the room to explain itself
 * rather than the height left over inside a modal.
 */
export default function PerDiemMealRulesCreate({
    perDiemConfiguration,
    mealTypes,
    nextSortOrder,
}: CreateMealRuleProps) {
    const { data, setData, post, errors, processing } = useForm<MealRuleInput>({
        meal_type: mealTypes[0]?.code ?? '',
        amount: '',
        cutoff_time: '',
        sort_order: String(nextSortOrder),
    });

    const selectedMealType = mealTypes.find(
        (mealType) => mealType.code === data.meal_type,
    );

    const mealTypeOptions = mealTypes.map((mealType) => ({
        value: mealType.code,
        label: mealType.name,
    }));

    const handleMealTypeChange = (value: string) => {
        const next = mealTypes.find((mealType) => mealType.code === value);

        setData((current) => ({
            ...current,
            meal_type: value,
            cutoff_time: next?.has_cutoff ? current.cutoff_time : '',
        }));
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(
            perDiemConfigurationRoutes.mealRules.store(perDiemConfiguration.id)
                .url,
        );
    };

    return (
        <>
            <Head title="Add Meal Rule" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title={`Add Meal Rule — ${perDiemConfiguration.name}`}
                    description="Add a meal, its amount, and the cutoff time used to deduct it from the claim."
                />

                <Button variant="ghost" className="w-fit" asChild>
                    <a
                        href={
                            perDiemConfigurationRoutes.manage(
                                perDiemConfiguration,
                            ).url
                        }
                    >
                        <ArrowLeft />
                        Back to meal rules
                    </a>
                </Button>

                <form
                    onSubmit={handleSubmit}
                    className="w-full space-y-6 rounded-lg border bg-card p-6 shadow-sm"
                >
                    <div className="space-y-2">
                        <Label htmlFor="meal_type">Meal *</Label>
                        <CustomComboBox
                            id="meal_type"
                            items={mealTypeOptions}
                            value={data.meal_type}
                            onSelect={(value) =>
                                value && handleMealTypeChange(value)
                            }
                            placeholder="Select Meal"
                        />
                        {mealTypes.length === 0 && (
                            <p className="text-xs text-destructive">
                                No active meal types are available. Contact an
                                administrator.
                            </p>
                        )}
                        <InputError message={errors.meal_type} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="amount">Amount *</Label>
                        <Input
                            id="amount"
                            name="amount"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.amount}
                            onChange={(event) =>
                                setData('amount', event.target.value)
                            }
                            required
                            autoFocus
                            placeholder="e.g., 250.00"
                        />
                        <p className="text-xs text-muted-foreground">
                            Amount deducted from the per diem for this meal.
                        </p>
                        <InputError message={errors.amount} />
                    </div>

                    {selectedMealType?.has_cutoff && (
                        <div className="space-y-2">
                            <Label htmlFor="cutoff_time">Cutoff Time</Label>
                            <Input
                                id="cutoff_time"
                                name="cutoff_time"
                                type="time"
                                value={data.cutoff_time}
                                onChange={(event) =>
                                    setData('cutoff_time', event.target.value)
                                }
                                placeholder="HH:MM"
                            />
                            <p className="text-xs text-muted-foreground">
                                Deducted when the traveler departs the trip
                                origin after this time, or arrives back at the
                                origin before it.
                            </p>
                            <InputError message={errors.cutoff_time} />
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="sort_order">Order *</Label>
                        <Input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            step="1"
                            value={data.sort_order}
                            onChange={(event) =>
                                setData('sort_order', event.target.value)
                            }
                            required
                            placeholder="e.g., 1"
                        />
                        <p className="text-xs text-muted-foreground">
                            Determines display order (lowest first).
                        </p>
                        <InputError message={errors.sort_order} />
                    </div>

                    <div className="flex justify-end gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.get(
                                    perDiemConfigurationRoutes.manage(
                                        perDiemConfiguration,
                                    ).url,
                                )
                            }
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Add Meal Rule
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PerDiemMealRulesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Per Diem Configurations',
            href: perDiemConfigurationRoutes.index().url,
        },
        {
            title: 'Meal Rules',
            href: perDiemConfigurationRoutes.index().url,
        },
    ],
};
