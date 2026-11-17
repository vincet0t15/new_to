import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import expenseTypesRoutes from '@/routes/expense-types';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { ExpenseType } from '@/types/travel-setup';
import type { KeyboardEvent } from 'react';

type Props = { expenseTypes: PaginatedDataResponse<ExpenseType>; filters: { search?: string } };

export default function ExpenseTypesIndex({ expenseTypes, filters }: Props) {
    const { data, setData } = useForm({ search: filters.search ?? '' });
    const handleSearchKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        router.get(expenseTypesRoutes.index().url, data.search ? { search: data.search } : undefined, { preserveState: true, preserveScroll: true });
    };
    return (
        <>
            <Head title="Expense Types" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading title="Expense Types" description="Manage expense types used in travel orders." />
                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="expense-types.create">
                        <Button onClick={() => router.visit(expenseTypesRoutes.create().url)}><Plus /> New Expense Type</Button>
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
                                <TableHead>Default</TableHead>
                                <TableHead>Transportation</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {expenseTypes.data.length > 0 ? expenseTypes.data.map((m) => (
                                <TableRow key={m.id}>
                                    <TableCell className="font-medium">{m.name}</TableCell>
                                    <TableCell className="max-w-[240px] truncate">{m.description ?? '—'}</TableCell>
                                    <TableCell>{m.is_default ? 'Yes' : 'No'}</TableCell>
                                    <TableCell>{m.is_transportation ? 'Yes' : 'No'}</TableCell>
                                    <TableCell>{m.is_active ? 'Active' : 'Inactive'}</TableCell>
                                    <TableCell className="flex items-center justify-end gap-2">
                                        <Can permission="expense-types.update"><Button variant="ghost" size="icon" className="h-8 w-8 text-green-600" onClick={() => router.visit(expenseTypesRoutes.edit(m).url)} title="Edit"><Pencil /></Button></Can>
                                        <Can permission="expense-types.delete"><Button variant="ghost" size="icon" className="h-8 w-8 text-red-600" onClick={() => router.visit(expenseTypesRoutes.delete(m).url)} title="Delete"><Trash2 /></Button></Can>
                                    </TableCell>
                                </TableRow>
                            )) : (
                                <TableRow><TableCell colSpan={6} className="h-24 text-center text-muted-foreground">No expense types found.</TableCell></TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                <Pagination data={expenseTypes} />
            </div>
        </>
    );
}
ExpenseTypesIndex.layout = { breadcrumbs: [{ title: 'Expense Types', href: expenseTypesRoutes.index().url }] };
