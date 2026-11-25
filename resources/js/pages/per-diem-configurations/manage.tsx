import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Plus, Trash2, Utensils } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { formatMoney } from '@/lib/travel-setup-format';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import perDiemConfigurationRoutes from '@/routes/per-diem-configurations';
import type {
    MealRule,
    PerDiemConfiguration,
} from '@/types/per-diem-configuration';

type ManagePerDiemConfigurationProps = {
    perDiemConfiguration: PerDiemConfiguration;
    mealRules: MealRule[];
    dailyRate: string;
};

export default function ManagePerDiemConfiguration({
    perDiemConfiguration,
    mealRules,
    dailyRate,
}: ManagePerDiemConfigurationProps) {
    const nextSortOrder =
        mealRules.reduce(
            (highest, mealRule) => Math.max(highest, mealRule.sort_order),
            0,
        ) + 1;

    return (
        <>
            <Head title={`Meal Rules — ${perDiemConfiguration.name}`} />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title={`${perDiemConfiguration.name} — Meal Rules`}
                    description="Define the meal breakdown and cutoff times used to compute the per diem claim."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Button
                        variant="ghost"
                        size="sm"
                        asChild
                        className="w-fit text-muted-foreground"
                    >
                        <Link
                            href={perDiemConfigurationRoutes.index().url}
                            className="inline-flex items-center gap-1"
                        >
                            <ArrowLeft className="size-4" />
                            Back to Per Diem Configurations
                        </Link>
                    </Button>

                    <Can permission="per-diem-configurations.create">
                        <Button
                            onClick={() =>
                                router.get(
                                    perDiemConfigurationRoutes.mealRules.create(
                                        perDiemConfiguration,
                                    ).url,
                                    { next_sort_order: nextSortOrder },
                                )
                            }
                        >
                            <Plus />
                            Add Meal Rule
                        </Button>
                    </Can>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="outline"
                        className="border-transparent bg-emerald-100 text-emerald-700"
                    >
                        <Utensils className="size-3" />
                        {mealRules.length} meal(s)
                    </Badge>
                    <Badge
                        variant="outline"
                        className="border-transparent bg-blue-100 text-blue-700"
                    >
                        Daily Rate: {formatMoney(dailyRate)}
                    </Badge>
                </div>

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">Order</TableHead>
                                <TableHead>Meal</TableHead>
                                <TableHead className="text-right">
                                    Amount
                                </TableHead>
                                <TableHead className="text-right">
                                    Cutoff
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {mealRules.length > 0 ? (
                                mealRules.map((mealRule) => (
                                    <TableRow key={mealRule.id}>
                                        <TableCell className="w-12 text-muted-foreground">
                                            {mealRule.sort_order}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className="border-transparent bg-muted"
                                            >
                                                {mealRule.meal_type_option
                                                    ?.name ??
                                                    mealRule.meal_type}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatMoney(mealRule.amount)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {mealRule.cutoff_time ?? (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="per-diem-configurations.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    asChild
                                                >
                                                    <Link
                                                        href={
                                                            perDiemConfigurationRoutes.mealRules.edit(
                                                                [
                                                                    perDiemConfiguration.id,
                                                                    mealRule.id,
                                                                ],
                                                            ).url
                                                        }
                                                        title="Edit meal rule"
                                                    >
                                                        <Pencil />
                                                        <span className="sr-only">
                                                            Edit{' '}
                                                            {mealRule
                                                                .meal_type_option
                                                                ?.name ??
                                                                mealRule.meal_type}
                                                        </span>
                                                    </Link>
                                                </Button>
                                            </Can>
                                            <Can permission="per-diem-configurations.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    asChild
                                                >
                                                    <Link
                                                        href={
                                                            perDiemConfigurationRoutes.mealRules.delete(
                                                                [
                                                                    perDiemConfiguration.id,
                                                                    mealRule.id,
                                                                ],
                                                            ).url
                                                        }
                                                        title="Delete meal rule"
                                                    >
                                                        <Trash2 />
                                                        <span className="sr-only">
                                                            Delete{' '}
                                                            {mealRule
                                                                .meal_type_option
                                                                ?.name ??
                                                                mealRule.meal_type}
                                                        </span>
                                                    </Link>
                                                </Button>
                                            </Can>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No meal rules yet. Add one to start
                                        building the daily rate.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

ManagePerDiemConfiguration.layout = {
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
