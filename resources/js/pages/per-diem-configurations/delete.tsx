import { Head, router, useForm } from '@inertiajs/react';
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

type DeletePerDiemConfigurationProps = {
    perDiemConfiguration: PerDiemConfiguration;
    mealRules: MealRule[];
    usageCount: number;
};

/**
 * Confirm the deletion of a per diem configuration.
 *
 * A screen rather than a dialog, and the reason is what has to be agreed to:
 * the meal rules are listed and their total is named, because deleting the
 * configuration takes them with it. A dialog hides that behind a scrollbar.
 */
export default function PerDiemConfigurationsDelete({
    perDiemConfiguration,
    mealRules,
    usageCount,
}: DeletePerDiemConfigurationProps) {
    const { delete: destroy, processing } = useForm();
    const isInUse = usageCount > 0;

    const handleSubmit = () => {
        destroy(perDiemConfigurationRoutes.destroy(perDiemConfiguration).url);
    };

    return (
        <>
            <Head title="Delete Per Diem Configuration" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Delete Per Diem Configuration"
                    description="This cannot be undone."
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

                <div className="w-full max-w-2xl space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                    {isInUse ? (
                        <Alert variant="destructive">
                            <AlertTriangle />
                            <AlertDescription>
                                "{perDiemConfiguration.name}" is used by{' '}
                                {usageCount} record(s) and cannot be deleted.
                            </AlertDescription>
                        </Alert>
                    ) : (
                        <Alert>
                            <AlertTriangle />
                            <AlertDescription>
                                Are you sure you want to delete "
                                {perDiemConfiguration.name}"? Its meal rules
                                will be deleted too.
                            </AlertDescription>
                        </Alert>
                    )}

                    {mealRules.length > 0 && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium">
                                Meal rules that will be removed
                            </p>
                            <ul className="divide-y rounded-md border text-sm">
                                {mealRules.map((mealRule) => (
                                    <li
                                        key={mealRule.id}
                                        className="flex items-center justify-between gap-4 px-3 py-2"
                                    >
                                        <span>
                                            {mealRule.meal_type_option?.name ??
                                                mealRule.meal_type}
                                        </span>
                                        <span className="text-muted-foreground tabular-nums">
                                            {Number(
                                                mealRule.amount,
                                            ).toLocaleString('en-PH', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

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
                        {!isInUse && (
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={handleSubmit}
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Delete Configuration
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

PerDiemConfigurationsDelete.layout = {
    breadcrumbs: [
        {
            title: 'Per Diem Configurations',
            href: perDiemConfigurationRoutes.index().url,
        },
        {
            title: 'Delete',
            href: perDiemConfigurationRoutes.index().url,
        },
    ],
};
