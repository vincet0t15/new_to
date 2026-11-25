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
import locationsRoutes from '@/routes/locations';
import type { Location } from '@/types/location';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateLocationDialog } from './create';
import { DeleteLocationDialog } from './delete';
import { EditLocationDialog } from './edit';

type LocationsIndexProps = {
    locations: PaginatedDataResponse<Location>;
    filters: {
        search?: string;
    };
};

export default function LocationsIndex({
    locations,
    filters,
}: LocationsIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selectedLocation, setSelectedLocation] = useState<Location | null>(
        null,
    );
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleEdit = (location: Location) => {
        setSelectedLocation(location);
        setEditOpen(true);
    };

    const handleDelete = (location: Location) => {
        setSelectedLocation(location);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            locationsRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Locations" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Location Management"
                    description="Create, update, and manage the destinations and origins available to travel orders."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="locations.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Location
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search locations..."
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
                            {locations.data.length > 0 ? (
                                locations.data.map((location) => (
                                    <TableRow key={location.id}>
                                        <TableCell className="font-medium">
                                            {location.name}
                                        </TableCell>
                                        <TableCell>
                                            {location.description || (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            {location.is_active ? (
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
                                            {location.usage_count > 0 ? (
                                                <Badge
                                                    variant="outline"
                                                    className="border-transparent bg-blue-100 text-blue-700"
                                                >
                                                    {location.usage_count} in
                                                    use
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="locations.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    onClick={() =>
                                                        handleEdit(location)
                                                    }
                                                    title="Edit location"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="locations.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() =>
                                                        handleDelete(location)
                                                    }
                                                    title={
                                                        location.usage_count > 0
                                                            ? 'In use — cannot be deleted'
                                                            : 'Delete location'
                                                    }
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
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No locations found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={locations} />

                {createOpen && (
                    <CreateLocationDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                    />
                )}

                {editOpen && selectedLocation && (
                    <EditLocationDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        location={selectedLocation}
                    />
                )}

                {deleteOpen && selectedLocation && (
                    <DeleteLocationDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        location={selectedLocation}
                    />
                )}
            </div>
        </>
    );
}

LocationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/admin/settings',
        },
        {
            title: 'Locations',
            href: locationsRoutes.index().url,
        },
    ],
};
