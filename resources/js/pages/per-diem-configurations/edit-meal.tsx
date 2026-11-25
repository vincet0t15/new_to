import { Head, Link, router, useForm } from '@inertiajs/react';
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
    MealRule,
    MealRuleInput,
    MealTypeOption,
    PerDiemConfiguration,
} from '@/types/per-diem-configuration';
import type { FormEvent } from 'react';

type EditMealRuleProps = {
    perDiemConfiguration: PerDiemConfiguration;
    mealRule: MealRule;
    mealTypes: MealTypeOption[];
};

/**
 * Edit a meal rule.
 *
 * A page of its own for the same reason as adding one: what a meal amount and
 * its cutoff decide about a claim is worth stating on its own screen.
 */
export default function PerDiemMealRulesEdit({
    perDiemConfiguration,
    mealRule,
    mealTypes,
}: EditMealRuleProps) {
    const { data, setData, put, errors, processing } = useForm<MealRuleInput>({
        meal_type: mealRule.meal_type,
        amount: mealRule.amount,
        cutoff_time: mealRule.cutoff_time ?? '',
        sort_order: String(mealRule.sort_order),
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

        put(
            perDiemConfigurationRoutes.mealRules.update([
                perDiemConfiguration.id,
                mealRule.id,
            ]).url,
        );
    };

    return (
        <>
            <Head title="Edit Meal Rule" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title={`Edit Meal Rule — ${perDiemConfiguration.name}`}
                    description="Update the amount, cutoff time and order of this meal."
                />

                <Button variant="ghost" className="w-fit" asChild>
                    <Link
                        href={
                            perDiemConfigurationRoutes.manage(
                                perDiemConfiguration,
                            ).url
                        }
                    >
                        <ArrowLeft />
                        Back to meal rules
                    </Link>
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
                            placeholder="e.g., 100.00"
                            value={data.amount}
                            onChange={(event) =>
                                setData('amount', event.target.value)
                            }
                            required
                            autoFocus
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
                            placeholder="e.g., 1"
                            value={data.sort_order}
                            onChange={(event) =>
                                setData('sort_order', event.target.value)
                            }
                            required
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
                            Save Changes
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PerDiemMealRulesEdit.layout = {
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
