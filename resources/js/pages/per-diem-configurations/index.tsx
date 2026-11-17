import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import perDiemConfigurationsRoutes from '@/routes/per-diem-configurations';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { PerDiemConfiguration } from '@/types/travel-setup';
import type { KeyboardEvent } from 'react';

type Props = { perDiemConfigurations: PaginatedDataResponse<PerDiemConfiguration>; filters: { search?: string } };

export default function PerDiemConfigurationsIndex({ perDiemConfigurations, filters }: Props) {
    const { data, setData } = useForm({ search: filters.search ?? '' });
    const handleSearchKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        router.get(perDiemConfigurationsRoutes.index().url, data.search ? { search: data.search } : undefined, { preserveState: true, preserveScroll: true });
    };
    return (
        <>
            <Head title="Per Diem Configurations" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading title="Per Diem Configurations" description="Manage per diem rates." />
                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="per-diem-configurations.create">
                        <Button onClick={() => router.visit(perDiemConfigurationsRoutes.create().url)}><Plus /> New Per Diem Configuration</Button>
                    </Can>
                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">Search</Label>
                        <Input id="search" placeholder="Search..." className="w-full pl-8" value={data.search} onChange={(e) => setData('search', e.target.value)} onKeyDown={handleSearchKeyDown} />
                        <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 opacity-50 select-none" />
                    </div>
                </div>
                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Effective From</TableHead>
                                <TableHead>Effective Until</TableHead>
                                <TableHead>Daily Rate</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {perDiemConfigurations.data.length > 0 ? perDiemConfigurations.data.map((m) => (
                                <TableRow key={m.id}>
                                    <TableCell className="font-medium">{m.name}</TableCell>
                                    <TableCell>{m.effective_from ?? '—'}</TableCell>
                                    <TableCell>{m.effective_until ?? '—'}</TableCell>
                                    <TableCell>{m.daily_rate ?? '—'}</TableCell>
                                    <TableCell>{m.is_active ? 'Active' : 'Inactive'}</TableCell>
                                    <TableCell className="flex items-center justify-end gap-2">
                                        <Can permission="per-diem-configurations.update"><Button variant="ghost" size="icon" className="h-8 w-8 text-green-600" onClick={() => router.visit(perDiemConfigurationsRoutes.edit(m).url)} title="Edit"><Pencil /></Button></Can>
                                        <Can permission="per-diem-configurations.delete"><Button variant="ghost" size="icon" className="h-8 w-8 text-red-600" onClick={() => router.visit(perDiemConfigurationsRoutes.delete(m).url)} title="Delete"><Trash2 /></Button></Can>
                                    </TableCell>
                                </TableRow>
                            )) : (
                                <TableRow><TableCell colSpan={6} className="h-24 text-center text-muted-foreground">No per diem configurations found.</TableCell></TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                <Pagination data={perDiemConfigurations} />
            </div>
        </>
    );
}
PerDiemConfigurationsIndex.layout = { breadcrumbs: [{ title: 'Per Diem Configurations', href: perDiemConfigurationsRoutes.index().url }] };
