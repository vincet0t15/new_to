import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
import travelClassificationsRoutes from '@/routes/travel-classifications';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { TravelClassification } from '@/types/travel-classification';
import type { KeyboardEvent } from 'react';
import { CreateTravelClassificationDialog } from './create';
import { DeleteTravelClassificationDialog } from './delete';
import { EditTravelClassificationDialog } from './edit';

type TravelClassificationsIndexProps = {
    travelClassifications: PaginatedDataResponse<TravelClassification>;
    filters: {
        search?: string;
    };
};

export default function TravelClassificationsIndex({
    travelClassifications,
    filters,
}: TravelClassificationsIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selected, setSelected] = useState<TravelClassification | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleEdit = (travelClassification: TravelClassification) => {
        setSelected(travelClassification);
        setEditOpen(true);
    };

    const handleDelete = (travelClassification: TravelClassification) => {
        setSelected(travelClassification);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            travelClassificationsRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Travel Classifications" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Travel Classifications"
                    description="Create, update, and manage the classifications applied to travel orders."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="travel-classifications.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Travel Classification
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
                            {travelClassifications.data.length > 0 ? (
                                travelClassifications.data.map(
                                    (travelClassification) => (
                                        <TableRow key={travelClassification.id}>
                                            <TableCell className="font-medium">
                                                {travelClassification.name}
                                            </TableCell>
                                            <TableCell>
                                                {travelClassification.description ||
                                                    '—'}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {travelClassification.is_active ? (
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
                                                {travelClassification.usage_count >
                                                0 ? (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-transparent bg-blue-100 text-blue-700"
                                                    >
                                                        {
                                                            travelClassification.usage_count
                                                        }{' '}
                                                        in use
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="flex items-center justify-end gap-2">
                                                <Can permission="travel-classifications.update">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                        onClick={() =>
                                                            handleEdit(
                                                                travelClassification,
                                                            )
                                                        }
                                                        title="Edit travel classification"
                                                    >
                                                        <Pencil />
                                                    </Button>
                                                </Can>
                                                <Can permission="travel-classifications.delete">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                        onClick={() =>
                                                            handleDelete(
                                                                travelClassification,
                                                            )
                                                        }
                                                        title={
                                                            travelClassification.usage_count >
                                                            0
                                                                ? 'In use — cannot be deleted'
                                                                : 'Delete travel classification'
                                                        }
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </Can>
                                            </TableCell>
                                        </TableRow>
                                    ),
                                )
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No travel classifications found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={travelClassifications} />

                {createOpen && (
                    <CreateTravelClassificationDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                    />
                )}

                {editOpen && selected && (
                    <EditTravelClassificationDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        travelClassification={selected}
                    />
                )}

                {deleteOpen && selected && (
                    <DeleteTravelClassificationDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        travelClassification={selected}
                    />
                )}
            </div>
        </>
    );
}

TravelClassificationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/admin/settings',
        },
        {
            title: 'Travel Classifications',
            href: travelClassificationsRoutes.index().url,
        },
    ],
};
