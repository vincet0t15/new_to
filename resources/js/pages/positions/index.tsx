import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
import positionsRoutes from '@/routes/positions';
import type { EmploymentType } from '@/types/employment-type';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { Position } from '@/types/position';
import type { KeyboardEvent } from 'react';
import { CreatePositionDialog } from './create';
import { DeletePositionDialog } from './delete';
import { EditPositionDialog } from './edit';

type PositionsIndexProps = {
    positions: PaginatedDataResponse<Position>;
    employmentTypes: Pick<EmploymentType, 'id' | 'name'>[];
    filters: {
        search?: string;
    };
};

export default function PositionsIndex({
    positions,
    employmentTypes,
    filters,
}: PositionsIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selectedPosition, setSelectedPosition] = useState<Position | null>(
        null,
    );
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleEdit = (position: Position) => {
        setSelectedPosition(position);
        setEditOpen(true);
    };

    const handleDelete = (position: Position) => {
        setSelectedPosition(position);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            positionsRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Positions" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Position Management"
                    description="Create, update, and manage the job titles employees can hold."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="positions.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Position
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search positions..."
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
                                <TableHead>Position</TableHead>
                                <TableHead>Employment Type</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {positions.data.length > 0 ? (
                                positions.data.map((position) => (
                                    <TableRow key={position.id}>
                                        <TableCell className="font-medium">
                                            {position.name}
                                        </TableCell>
                                        <TableCell>
                                            {position.employment_type?.name || (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {position.description || (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="positions.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    onClick={() =>
                                                        handleEdit(position)
                                                    }
                                                    title="Edit position"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="positions.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() =>
                                                        handleDelete(position)
                                                    }
                                                    title="Delete position"
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
                                        colSpan={4}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No positions found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={positions} />

                {createOpen && (
                    <CreatePositionDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                        employmentTypes={employmentTypes}
                    />
                )}

                {editOpen && selectedPosition && (
                    <EditPositionDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        position={selectedPosition}
                        employmentTypes={employmentTypes}
                    />
                )}

                {deleteOpen && selectedPosition && (
                    <DeletePositionDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        position={selectedPosition}
                    />
                )}
            </div>
        </>
    );
}

PositionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Positions',
            href: positionsRoutes.index().url,
        },
    ],
};
