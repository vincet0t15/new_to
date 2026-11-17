import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
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
import type { TransportationMode } from '@/types/transportation-mode';
import type { KeyboardEvent } from 'react';

type Props = {
    transportationModes: PaginatedDataResponse<TransportationMode>;
    filters: { search?: string };
};

export default function TransportationModesIndex({
    transportationModes,
    filters,
}: Props) {
    const { data, setData } = useForm({ search: filters.search ?? '' });

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        router.get(
            transportationModesRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Transportation Modes" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Transportation Modes"
                    description="Manage the modes of transport used in travel orders."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="transportation-modes.create">
                        <Button
                            onClick={() =>
                                router.visit(
                                    transportationModesRoutes.create().url,
                                )
                            }
                        >
                            <Plus />
                            New Transportation Mode
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search..."
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
                                <TableHead>Description</TableHead>
                                <TableHead>Government</TableHead>
                                <TableHead>Fix Cost</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transportationModes.data.length > 0 ? (
                                transportationModes.data.map((mode) => (
                                    <TableRow key={mode.id}>
                                        <TableCell className="font-medium">
                                            {mode.name}
                                        </TableCell>
                                        <TableCell className="max-w-[240px] truncate">
                                            {mode.description ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {mode.is_government ? 'Yes' : 'No'}
                                        </TableCell>
                                        <TableCell>
                                            {mode.fix_cost ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {mode.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="transportation-modes.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600"
                                                    onClick={() =>
                                                        router.visit(
                                                            transportationModesRoutes.edit(
                                                                mode,
                                                            ).url,
                                                        )
                                                    }
                                                    title="Edit"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="transportation-modes.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600"
                                                    onClick={() =>
                                                        router.visit(
                                                            transportationModesRoutes.delete(
                                                                mode,
                                                            ).url,
                                                        )
                                                    }
                                                    title="Delete"
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </Can>
                                        </TableCell>
                                    </TableRow>
                                ))
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
            title: 'Transportation Modes',
            href: transportationModesRoutes.index().url,
        },
    ],
};
