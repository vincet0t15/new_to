import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import vehiclesRoutes from '@/routes/vehicles';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { TripTicketDriver, Vehicle } from '@/types';
import type { KeyboardEvent } from 'react';
import { CreateVehicleDialog } from './create';
import { DeleteVehicleDialog } from './delete';
import { EditVehicleDialog } from './edit';

type Props = {
    vehicles: PaginatedDataResponse<Vehicle>;
    drivers: TripTicketDriver[];
    filters: { search: string };
};

export default function VehicleIndex({ vehicles, drivers, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isDeleteOpen, setIsDeleteOpen] = useState(false);
    const [selected, setSelected] = useState<Vehicle | null>(null);

    const doSearch = () =>
        router.get(
            vehiclesRoutes.index().url,
            { search: search || undefined },
            { preserveState: true, replace: true },
        );

    return (
        <>
            <Head title="Vehicles" />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Vehicles"
                        description="Registered government vehicles and their assigned drivers."
                    />
                    <Can permission="vehicles.create">
                        <Button onClick={() => setIsCreateOpen(true)}>
                            <Plus className="mr-2 size-4" />
                            Register vehicle
                        </Button>
                    </Can>
                </div>

                <div className="flex items-center gap-4">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search vehicles or drivers..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            onKeyDown={(
                                event: KeyboardEvent<HTMLInputElement>,
                            ) => event.key === 'Enter' && doSearch()}
                            className="max-w-sm pl-9"
                        />
                    </div>
                    <Button variant="outline" onClick={doSearch}>
                        Search
                    </Button>
                </div>

                <div className="overflow-hidden rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Plate number</TableHead>
                                <TableHead>Type / Model</TableHead>
                                <TableHead>Capacity</TableHead>
                                <TableHead>Driver</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Trips</TableHead>
                                <TableHead className="w-[100px]">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vehicles.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center"
                                    >
                                        No vehicles registered yet.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                vehicles.data.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            {item.plate_no}
                                        </TableCell>
                                        <TableCell>
                                            <span>
                                                {item.vehicle_type ?? '—'}
                                            </span>
                                            {item.model && (
                                                <span className="block text-sm text-muted-foreground">
                                                    {item.model}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {item.capacity ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {item.driver?.full_name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    item.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {item.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {item.usage_count}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Can permission="vehicles.update">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Edit ${item.plate_no}`}
                                                        onClick={() => {
                                                            setSelected(item);
                                                            setIsEditOpen(true);
                                                        }}
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Button>
                                                </Can>
                                                <Can permission="vehicles.delete">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Delete ${item.plate_no}`}
                                                        disabled={
                                                            item.usage_count > 0
                                                        }
                                                        onClick={() => {
                                                            setSelected(item);
                                                            setIsDeleteOpen(
                                                                true,
                                                            );
                                                        }}
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </Can>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={vehicles} />
            </div>

            <CreateVehicleDialog
                isOpen={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
                drivers={drivers}
            />
            {selected && (
                <EditVehicleDialog
                    isOpen={isEditOpen}
                    onClose={() => {
                        setIsEditOpen(false);
                        setSelected(null);
                    }}
                    vehicle={selected}
                    drivers={drivers}
                />
            )}
            {selected && (
                <DeleteVehicleDialog
                    isOpen={isDeleteOpen}
                    onClose={() => {
                        setIsDeleteOpen(false);
                        setSelected(null);
                    }}
                    vehicle={selected}
                />
            )}
        </>
    );
}
