import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import perDiemConfigurationRoutes from '@/routes/per-diem-configurations';
import type {
    MealRule,
    PerDiemConfiguration,
} from '@/types/per-diem-configuration';

type DeleteMealRuleProps = {
    perDiemConfiguration: PerDiemConfiguration;
    mealRule: MealRule;
    dailyRate: string;
};

/**
 * Confirm the removal of a meal rule.
 *
 * A screen rather than a dialog, and the reason is the arithmetic: the daily
 * rate is the sum of what is left, so removing a meal lowers the rate by its
 * amount. That new rate is shown before the removal is agreed to.
 */
export default function PerDiemMealRulesDelete({
    perDiemConfiguration,
    mealRule,
    dailyRate,
}: DeleteMealRuleProps) {
    const { delete: destroy, processing } = useForm();

    const current = Number(mealRule.amount);
    const after = Number(dailyRate) - current;

    return (
        <>
            <Head title="Delete Meal Rule" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title={`Delete Meal Rule — ${perDiemConfiguration.name}`}
                    description="This cannot be undone."
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

                <div className="w-full max-w-lg space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                    <Alert>
                        <AlertTriangle />
                        <AlertDescription>
                            Are you sure you want to delete "
                            {mealRule.meal_type_option?.name ??
                                mealRule.meal_type}
                            " from {perDiemConfiguration.name}?
                        </AlertDescription>
                    </Alert>

                    <dl className="grid grid-cols-2 gap-y-3 text-sm">
                        <dt className="text-muted-foreground">Meal</dt>
                        <dd className="text-right font-medium">
                            {mealRule.meal_type_option?.name ??
                                mealRule.meal_type}
                        </dd>

                        <dt className="text-muted-foreground">
                            Amount removed
                        </dt>
                        <dd className="text-right font-medium text-red-600 tabular-nums">
                            −
                            {current.toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}
                        </dd>

                        <dt className="text-muted-foreground">
                            Daily rate now
                        </dt>
                        <dd className="text-right font-medium tabular-nums">
                            {Number(dailyRate).toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}
                        </dd>

                        <dt className="font-medium">Daily rate after</dt>
                        <dd className="text-right font-semibold tabular-nums">
                            {after.toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}
                        </dd>
                    </dl>

                    <div className="flex justify-end gap-2 border-t pt-4">
                        <Button variant="outline" asChild>
                            <Link
                                href={
                                    perDiemConfigurationRoutes.manage(
                                        perDiemConfiguration,
                                    ).url
                                }
                            >
                                Cancel
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                destroy(
                                    perDiemConfigurationRoutes.mealRules.destroy(
                                        [perDiemConfiguration.id, mealRule.id],
                                    ).url,
                                )
                            }
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Delete Meal Rule
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

PerDiemMealRulesDelete.layout = {
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
