import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import fundSourcesRoutes from '@/routes/fund-sources';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { FundSource } from '@/types/travel-setup';
import type { KeyboardEvent } from 'react';

type Props = { fundSources: PaginatedDataResponse<FundSource>; filters: { search?: string } };

export default function FundSourcesIndex({ fundSources, filters }: Props) {
    const { data, setData } = useForm({ search: filters.search ?? '' });
    const handleSearchKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        router.get(fundSourcesRoutes.index().url, data.search ? { search: data.search } : undefined, { preserveState: true, preserveScroll: true });
    };
    return (
        <>
            <Head title="Fund Sources" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading title="Fund Sources" description="Manage fund sources used in travel orders." />
                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="fund-sources.create">
                        <Button onClick={() => router.visit(fundSourcesRoutes.create().url)}><Plus /> New Fund Source</Button>
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
                                <TableHead>Description</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fundSources.data.length > 0 ? fundSources.data.map((m) => (
                                <TableRow key={m.id}>
                                    <TableCell className="font-medium">{m.name}</TableCell>
                                    <TableCell className="max-w-[240px] truncate">{m.description ?? '—'}</TableCell>
                                    <TableCell>{m.is_active ? 'Active' : 'Inactive'}</TableCell>
                                    <TableCell className="flex items-center justify-end gap-2">
                                        <Can permission="fund-sources.update"><Button variant="ghost" size="icon" className="h-8 w-8 text-green-600" onClick={() => router.visit(fundSourcesRoutes.edit(m).url)} title="Edit"><Pencil /></Button></Can>
                                        <Can permission="fund-sources.delete"><Button variant="ghost" size="icon" className="h-8 w-8 text-red-600" onClick={() => router.visit(fundSourcesRoutes.delete(m).url)} title="Delete"><Trash2 /></Button></Can>
                                    </TableCell>
                                </TableRow>
                            )) : (
                                <TableRow><TableCell colSpan={4} className="h-24 text-center text-muted-foreground">No fund sources found.</TableCell></TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                <Pagination data={fundSources} />
            </div>
        </>
    );
}
FundSourcesIndex.layout = { breadcrumbs: [{ title: 'Fund Sources', href: fundSourcesRoutes.index().url }] };
