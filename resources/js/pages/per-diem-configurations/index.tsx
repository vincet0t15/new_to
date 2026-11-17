import { Head, Link, router, useForm } from '@inertiajs/react';
import { ListTree, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { formatMoney, formatShortDate } from '@/lib/travel-setup-format';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import perDiemConfigurationRoutes from '@/routes/per-diem-configurations';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { PerDiemConfiguration } from '@/types/per-diem-configuration';
import type { KeyboardEvent } from 'react';

type PerDiemConfigurationsIndexProps = {
    perDiemConfigurations: PaginatedDataResponse<PerDiemConfiguration>;
    filters: {
        search?: string;
    };
};
/**
 * The destinations a configuration covers, kept short enough for a table cell.
 *
 * A configuration can cover every place an office travels to, and the list of
 * names grows without bound, so the cell shows the first few and a count of
 * what is left. The full list stays on the element's title for the times it
 * matters, which is never a scroll down the page.
 */
function CoverageCell({
    configuration,
}: {
    configuration: PerDiemConfiguration;
}) {
    const names =
        configuration.locations?.map((location) => location.name) ?? [];

    if (names.length === 0) {
        return <span className="text-muted-foreground">All destinations</span>;
    }

    const shown = names.slice(0, 3);
    const remaining = names.length - shown.length;

    return (
        <span title={names.join(', ')} className="block max-w-56 truncate">
            {shown.join(', ')}
            {remaining > 0 && (
                <span className="text-muted-foreground">
                    {' '}
                    +{remaining} more
                </span>
            )}
        </span>
    );
}

export default function PerDiemConfigurationsIndex({
    perDiemConfigurations,
    filters,
}: PerDiemConfigurationsIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            perDiemConfigurationRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Per Diem Configurations" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Per Diem Configurations"
                    description="Configure the daily rate, meal breakdown with cutoffs, and destination coverage of per diems."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="per-diem-configurations.create">
                        <Button
                            onClick={() =>
                                router.get(
                                    perDiemConfigurationRoutes.create().url,
                                )
                            }
                        >
                            <Plus />
                            Create Per Diem Configuration
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search configurations..."
                            className="w-full pl-8"
                            value={data.search}
                            onChange={(event) =>
                                setData('search', event.target.value)
                            }
                            onKeyDown={handleSearchKeyDown}
                        />
                        <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 opacity-50 select-none" />
                    </div>
                </div>

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Coverage</TableHead>
                                <TableHead>Effective</TableHead>
                                <TableHead className="text-right">
                                    Daily Rate
                                </TableHead>
                                <TableHead className="text-center">
                                    Status
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {perDiemConfigurations.data.length > 0 ? (
                                perDiemConfigurations.data.map(
                                    (configuration) => (
                                        <TableRow key={configuration.id}>
                                            <TableCell className="font-medium">
                                                {configuration.name}
                                            </TableCell>
                                            <TableCell>
                                                <CoverageCell
                                                    configuration={
                                                        configuration
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {configuration.effective_from ? (
                                                    <>
                                                        {formatShortDate(
                                                            configuration.effective_from,
                                                        )}{' '}
                                                        –{' '}
                                                        {configuration.effective_until
                                                            ? formatShortDate(
                                                                  configuration.effective_until,
                                                              )
                                                            : 'present'}
                                                    </>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {configuration.daily_rate ? (
                                                    formatMoney(
                                                        configuration.daily_rate,
                                                    )
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {configuration.is_active ? (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-transparent bg-green-100 text-green-700"
                                                    >
                                                        Active
                                                    </Badge>
                                                ) : (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-transparent bg-muted text-muted-foreground"
                                                    >
                                                        Inactive
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="flex items-center justify-end gap-2">
                                                <Can permission="per-diem-configurations.view">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-8 text-blue-600 hover:bg-blue-50 hover:text-blue-700"
                                                        title="Manage meal rules"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={
                                                                perDiemConfigurationRoutes.manage(
                                                                    configuration,
                                                                ).url
                                                            }
                                                            className="inline-flex items-center gap-1"
                                                        >
                                                            <ListTree />
                                                            Meals
                                                        </Link>
                                                    </Button>
                                                </Can>
                                                <Can permission="per-diem-configurations.update">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={
                                                                perDiemConfigurationRoutes.edit(
                                                                    configuration,
                                                                ).url
                                                            }
                                                            title="Edit per diem configuration"
                                                        >
                                                            <Pencil />
                                                            <span className="sr-only">
                                                                Edit{' '}
                                                                {
                                                                    configuration.name
                                                                }
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
                                                                perDiemConfigurationRoutes.delete(
                                                                    configuration,
                                                                ).url
                                                            }
                                                            title={
                                                                configuration.usage_count >
                                                                0
                                                                    ? 'In use — cannot be deleted'
                                                                    : 'Delete per diem configuration'
                                                            }
                                                        >
                                                            <Trash2 />
                                                            <span className="sr-only">
                                                                Delete{' '}
                                                                {
                                                                    configuration.name
                                                                }
                                                            </span>
                                                        </Link>
                                                    </Button>
                                                </Can>
                                            </TableCell>
                                        </TableRow>
                                    ),
                                )
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No per diem configurations found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={perDiemConfigurations} />
            </div>
        </>
    );
}

PerDiemConfigurationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/admin/settings',
        },
        {
            title: 'Per Diem Configurations',
            href: perDiemConfigurationRoutes.index().url,
        },
    ],
};
