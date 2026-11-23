import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import fuelTypesRoutes from '@/routes/fuel-types';
import type { FuelType } from '@/types/fuel-type';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { CreateFuelTypeDialog } from './create';
import { DeleteFuelTypeDialog } from './delete';
import { EditFuelTypeDialog } from './edit';

type Props = {
    fuelTypes: PaginatedDataResponse<FuelType>;
    filters: { search: string };
};

export default function FuelTypeIndex({ fuelTypes, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isDeleteOpen, setIsDeleteOpen] = useState(false);
    const [selected, setSelected] = useState<FuelType | null>(null);

    const doSearch = () => router.get(fuelTypesRoutes.index().url, { search: search || undefined }, { preserveState: true, replace: true });

    return (
        <>
            <Head title="Fuel Types" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Fuel Types" description="Manage fuel types used in trip tickets and allocations." />
                    <Can permission="fuel-types.create">
                        <Button onClick={() => setIsCreateOpen(true)}><Plus className="mr-2 size-4" />Add Fuel Type</Button>
                    </Can>
                </div>

                <div className="flex items-center gap-4">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder="Search fuel types..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e: KeyboardEvent<HTMLInputElement>) => e.key === 'Enter' && doSearch()} className="max-w-sm pl-9" />
                    </div>
                    <Button variant="outline" onClick={doSearch}>Search</Button>
                </div>

                <div className="overflow-hidden rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Usage</TableHead>
                                <TableHead className="w-[100px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fuelTypes.data.length === 0 ? (
                                <TableRow><TableCell colSpan={5} className="h-24 text-center">No fuel types found.</TableCell></TableRow>
                            ) : fuelTypes.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">{item.name}</TableCell>
                                    <TableCell className="max-w-[300px] truncate text-muted-foreground">{item.description || '—'}</TableCell>
                                    <TableCell><Badge variant={item.is_active ? 'default' : 'secondary'}>{item.is_active ? 'Active' : 'Inactive'}</Badge></TableCell>
                                    <TableCell className="text-muted-foreground">{item.usage_count}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-1">
                                            <Can permission="fuel-types.update">
                                                <Button variant="ghost" size="icon" onClick={() => { setSelected(item); setIsEditOpen(true); }}><Pencil className="size-4" /></Button>
                                            </Can>
                                            <Can permission="fuel-types.delete">
                                                <Button variant="ghost" size="icon" disabled={item.usage_count > 0} onClick={() => { setSelected(item); setIsDeleteOpen(true); }}><Trash2 className="size-4" /></Button>
                                            </Can>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={fuelTypes} />
            </div>

            <CreateFuelTypeDialog isOpen={isCreateOpen} onClose={() => setIsCreateOpen(false)} />
            {selected && <EditFuelTypeDialog isOpen={isEditOpen} onClose={() => { setIsEditOpen(false); setSelected(null); }} fuelType={selected} />}
            {selected && <DeleteFuelTypeDialog isOpen={isDeleteOpen} onClose={() => { setIsDeleteOpen(false); setSelected(null); }} fuelType={selected} />}
        </>
    );
}
