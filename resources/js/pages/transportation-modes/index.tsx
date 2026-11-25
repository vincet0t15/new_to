import { Head, router, useForm } from '@inertiajs/react';
import { ChevronDown, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { Fragment, useState } from 'react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
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
import transportationModesRoutes from '@/routes/transportation-modes';
import type { PaginatedDataResponse } from '@/types/pagination';
import type {
    TransportationFare,
    TransportationMode,
} from '@/types/transportation-mode';
import type { KeyboardEvent } from 'react';

type TransportationModesIndexProps = {
    transportationModes: PaginatedDataResponse<TransportationMode>;
    filters: {
        search?: string;
    };
};

function formatMoney(value: string | null): string {
    if (value === null || value === '') {
        return '—';
    }

    return Number(value).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function FareList({ fares }: { fares: TransportationFare[] }) {
    if (fares.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No per-destination fares yet.
            </p>
        );
    }

    return (
        <div className="overflow-hidden rounded-md border">
            <div className="grid grid-cols-[1fr_120px] border-b bg-muted/50 px-3 py-2 text-sm font-medium text-muted-foreground">
                <span>Destination</span>
                <span className="text-right">Amount</span>
            </div>
            {fares.map((fare) => (
                <div
                    key={fare.id}
                    className="grid grid-cols-[1fr_120px] border-b px-3 py-2 text-sm last:border-b-0"
                >
                    <span>{fare.location?.name ?? 'Unknown'}</span>
                    <span className="text-right tabular-nums">
                        {formatMoney(fare.amount)}
                    </span>
                </div>
            ))}
        </div>
    );
}

/**
 * What the row itself cannot show: the fares behind the coverage, and the
 * fixed costs the mode falls back on. Opened by clicking the row, because the
 * list is scanned by name and the details are wanted only once a name is found.
 */
function ModeDetails({ mode }: { mode: TransportationMode }) {
    return (
        <div className="space-y-4 bg-muted/30 p-4">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div className="space-y-1">
                    <p className="text-xs font-medium text-muted-foreground uppercase">
                        Description
                    </p>
                    <p className="text-sm">
                        {mode.description || 'No description provided.'}
                    </p>
                </div>

                <div className="space-y-1">
                    <p className="text-xs font-medium text-muted-foreground uppercase">
                        Default Fare (Fallback)
                    </p>
                    <p className="text-sm tabular-nums">
                        {mode.fix_cost
                            ? `₱${formatMoney(mode.fix_cost)}`
                            : 'None — every fare comes from the destination list below.'}
                    </p>
                </div>

                <div className="space-y-1">
                    <p className="text-xs font-medium text-muted-foreground uppercase">
                        Government Vehicle
                    </p>
                    <p className="text-sm">
                        {mode.is_government
                            ? 'Yes — no fare is charged.'
                            : 'No — fares are charged.'}
                    </p>
                </div>
            </div>

            <div className="space-y-2">
                <p className="text-xs font-medium text-muted-foreground uppercase">
                    Fare per Destination
                </p>
                <FareList fares={mode.fares} />
            </div>
        </div>
    );
}

export default function TransportationModesIndex({
    transportationModes,
    filters,
}: TransportationModesIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());

    const toggleExpanded = (id: number) => {
        setExpandedIds((previous) => {
            const next = new Set(previous);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            transportationModesRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Transportation Modes" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Transportation Mode Management"
                    description="Create, update, and manage the modes of transportation available on travel orders."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="transportation-modes.create">
                        <Button
                            onClick={() =>
                                router.get(
                                    transportationModesRoutes.create().url,
                                )
                            }
                        >
                            <Plus />
                            Create Transportation Mode
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            className="w-full pl-8"
                            value={data.search}
                            onChange={(event) =>
                                setData('search', event.target.value)
                            }
                            onKeyDown={handleSearchKeyDown}
                            placeholder="Search..."
                        />
                        <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 opacity-50 select-none" />
                    </div>
                </div>

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-8" />
                                <TableHead>Name</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-center">
                                    Status
                                </TableHead>
                                <TableHead className="text-center">
                                    Usage
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transportationModes.data.length > 0 ? (
                                transportationModes.data.map((mode) => {
                                    const expanded = expandedIds.has(mode.id);

                                    return (
                                        <Fragment key={mode.id}>
                                            <TableRow
                                                className="cursor-pointer"
                                                onClick={() =>
                                                    toggleExpanded(mode.id)
                                                }
                                            >
                                                <TableCell className="w-8 pr-0">
                                                    <ChevronDown
                                                        className={`size-4 text-muted-foreground transition-transform ${
                                                            expanded
                                                                ? 'rotate-180'
                                                                : ''
                                                        }`}
                                                    />
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {mode.name}
                                                </TableCell>
                                                <TableCell>
                                                    {mode.description || (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {mode.is_active ? (
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
                                                <TableCell className="text-center">
                                                    {mode.usage_count > 0 ? (
                                                        <Badge
                                                            variant="outline"
                                                            className="border-transparent bg-blue-100 text-blue-700"
                                                        >
                                                            {mode.usage_count}{' '}
                                                            in use
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="flex items-center justify-end gap-1">
                                                    <Can permission="transportation-modes.update">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                            asChild
                                                            onClick={(event) =>
                                                                event.stopPropagation()
                                                            }
                                                        >
                                                            <a
                                                                href={
                                                                    transportationModesRoutes.edit(
                                                                        mode,
                                                                    ).url
                                                                }
                                                                title="Edit transportation mode"
                                                            >
                                                                <Pencil />
                                                                <span className="sr-only">
                                                                    Edit{' '}
                                                                    {mode.name}
                                                                </span>
                                                            </a>
                                                        </Button>
                                                    </Can>
                                                    <Can permission="transportation-modes.delete">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                            asChild
                                                            onClick={(event) =>
                                                                event.stopPropagation()
                                                            }
                                                        >
                                                            <a
                                                                href={
                                                                    transportationModesRoutes.delete(
                                                                        mode,
                                                                    ).url
                                                                }
                                                                title={
                                                                    mode.usage_count >
                                                                    0
                                                                        ? 'In use — cannot be deleted'
                                                                        : 'Delete transportation mode'
                                                                }
                                                            >
                                                                <Trash2 />
                                                                <span className="sr-only">
                                                                    Delete{' '}
                                                                    {mode.name}
                                                                </span>
                                                            </a>
                                                        </Button>
                                                    </Can>
                                                </TableCell>
                                            </TableRow>

                                            {expanded && (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={6}
                                                        className="p-0"
                                                    >
                                                        <ModeDetails
                                                            mode={mode}
                                                        />
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </Fragment>
                                    );
                                })
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No transportation modes found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={transportationModes} />
            </div>
        </>
    );
}

TransportationModesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/admin/settings',
        },
        {
            title: 'Transportation Modes',
            href: transportationModesRoutes.index().url,
        },
    ],
};
