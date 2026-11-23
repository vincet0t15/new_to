import { Head, router, useForm } from '@inertiajs/react';
import { Landmark, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CustomComboBox } from '@/components/CustomComboBox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useCan } from '@/hooks/use-can';
import { formatDate } from '@/lib/date-format';
import type { PaginatedDataResponse } from '@/types';

type OfficeOption = { id: number; office_name: string };
type FuelTypeOption = { id: number; name: string };
type Allocation = {
    id: number;
    office_id: number;
    type: 'allocation' | 'transfer';
    source_office_id: number | null;
    fuel_type_id: number | null;
    allocation_date: string;
    reference_no: string | null;
    fuel_type: string | null;
    allocated_liters: string;
    allocated_amount: string;
    remarks: string | null;
    status: 'active' | 'voided';
    void_reason: string | null;
    office?: OfficeOption | null;
    source_office?: OfficeOption | null;
    fuel_type_record?: FuelTypeOption | null;
    creator?: { id: number; name: string } | null;
};

type Props = {
    allocations: PaginatedDataResponse<Allocation>;
    offices: OfficeOption[];
    fuelTypes: FuelTypeOption[];
    filters: { search: string; office_id: string; status: string; date_from: string; date_to: string };
    urls: { index: string; store: string; update: string; void: string; fuelMonitoring: string };
};

function money(value: string | number | null | undefined) {
    return `PHP ${Number(value ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function liters(value: string | number | null | undefined) {
    return `${Number(value ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} L`;
}

export default function FuelAllocationsIndex({ allocations, offices, fuelTypes, filters, urls }: Props) {
    const can = useCan();
    const canManage = can('fuel-allocations.manage');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [voidDialog, setVoidDialog] = useState<Allocation | null>(null);
    const [editing, setEditing] = useState<Allocation | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [officeId, setOfficeId] = useState(filters.office_id ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const form = useForm({
        type: 'allocation',
        office_id: '',
        source_office_id: '',
        allocation_date: new Date().toISOString().slice(0, 10),
        reference_no: '',
        fuel_type_id: '',
        fuel_type: '',
        allocated_liters: '',
        allocated_amount: '',
        remarks: '',
    });
    const voidForm = useForm({ void_reason: '' });

    const query = {
        search: search || undefined,
        office_id: officeId || undefined,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    };

    const submitFilter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(urls.index, query, { preserveState: true, preserveScroll: true });
    };

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        form.setData('allocation_date', new Date().toISOString().slice(0, 10));
        setDialogOpen(true);
    };

    const openEdit = (allocation: Allocation) => {
        setEditing(allocation);
        form.clearErrors();
        form.setData({
            type: allocation.type,
            office_id: String(allocation.office_id),
            source_office_id: allocation.source_office_id ? String(allocation.source_office_id) : '',
            allocation_date: allocation.allocation_date?.slice(0, 10) ?? '',
            reference_no: allocation.reference_no ?? '',
            fuel_type_id: allocation.fuel_type_id ? String(allocation.fuel_type_id) : '',
            fuel_type: allocation.fuel_type ?? '',
            allocated_liters: allocation.allocated_liters ?? '',
            allocated_amount: allocation.allocated_amount ?? '',
            remarks: allocation.remarks ?? '',
        });
        setDialogOpen(true);
    };

    const submitAllocation = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (editing) {
            form.put(urls.update.replace('__ID__', String(editing.id)), { preserveScroll: true, onSuccess: () => setDialogOpen(false) });
            return;
        }
        form.post(urls.store, { preserveScroll: true, onSuccess: () => { setDialogOpen(false); form.reset(); } });
    };

    const submitVoid = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!voidDialog) return;
        voidForm.post(urls.void.replace('__ID__', String(voidDialog.id)), { preserveScroll: true, onSuccess: () => { setVoidDialog(null); voidForm.reset(); } });
    };

    return (
        <>
            <Head title="Fuel Allocations" />
            <div className="flex h-full w-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 sm:p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading title="Fuel Allocations" description="Configure fuel allocations per office. You can also record office-to-office fuel transfers/borrowing. Consumption is tracked from trip tickets in Fuel Monitoring." />
                    {canManage && <Button onClick={openCreate}><Plus className="size-4" />Add allocation / transfer</Button>}
                </div>

                <Card>
                    <CardHeader><CardTitle className="text-base">Filters</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={submitFilter} className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <div className="space-y-2 xl:col-span-2">
                                <Label htmlFor="search">Search</Label>
                                <div className="relative">
                                    <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                    <Input id="search" className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Office, reference, fuel type, remarks..." />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>Office</Label>
                                <CustomComboBox
                                    items={[{ value: '', label: 'All offices' }, ...offices.map((office) => ({ value: String(office.id), label: office.office_name }))]}
                                    value={officeId}
                                    placeholder="All offices"
                                    onSelect={(v) => setOfficeId(v ?? '')}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Status</Label>
                                <CustomComboBox
                                    items={[{ value: '', label: 'All statuses' }, { value: 'active', label: 'Active' }, { value: 'voided', label: 'Voided' }]}
                                    value={status}
                                    placeholder="All statuses"
                                    onSelect={(v) => setStatus(v ?? '')}
                                />
                            </div>
                            <div className="space-y-2"><Label htmlFor="date_from">Date from</Label><Input id="date_from" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} /></div>
                            <div className="space-y-2"><Label htmlFor="date_to">Date to</Label><Input id="date_to" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} /></div>
                            <div className="flex items-end gap-2 xl:col-span-4">
                                <Button type="submit">Apply filters</Button>
                                <Button type="button" variant="outline" onClick={() => { setSearch(''); setOfficeId(''); setStatus(''); setDateFrom(''); setDateTo(''); router.get(urls.index); }}>Reset</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base"><Landmark className="size-4 text-muted-foreground" />Allocation records</CardTitle>
                        <CardDescription>Active allocations count toward office fuel balance. Voided allocations are kept for audit.</CardDescription>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <Table>
                            <TableHeader><TableRow>
                                <TableHead>Date</TableHead><TableHead>Office</TableHead><TableHead>Type / Source</TableHead><TableHead>Fuel type</TableHead><TableHead className="text-right">Liters</TableHead><TableHead className="text-right">Amount</TableHead><TableHead>Reference / Remarks</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Actions</TableHead>
                            </TableRow></TableHeader>
                            <TableBody>
                                {allocations.data.length > 0 ? allocations.data.map((allocation) => (
                                    <TableRow key={allocation.id}>
                                        <TableCell>{formatDate(allocation.allocation_date)}</TableCell>
                                        <TableCell className="font-medium">
                                            <button type="button" className="text-left underline-offset-2 hover:underline" onClick={() => router.get(urls.fuelMonitoring, { office_id: String(allocation.office_id) }, { preserveScroll: true })}>
                                                {allocation.office?.office_name ?? '—'}
                                            </button>
                                        </TableCell>
                                        <TableCell>
                                            <div className="space-y-1">
                                                <Badge variant={allocation.type === 'transfer' ? 'outline' : 'secondary'}>{allocation.type}</Badge>
                                                {allocation.type === 'transfer' && <p className="text-muted-foreground text-xs">Source: {allocation.source_office?.office_name ?? '—'}</p>}
                                            </div>
                                        </TableCell>
                                        <TableCell>{allocation.fuel_type_record?.name || allocation.fuel_type || '—'}</TableCell>
                                        <TableCell className={`text-right tabular-nums ${Number(allocation.allocated_liters) < 0 ? 'text-destructive' : ''}`}>{liters(allocation.allocated_liters)}</TableCell>
                                        <TableCell className={`text-right tabular-nums ${Number(allocation.allocated_amount) < 0 ? 'text-destructive' : ''}`}>{money(allocation.allocated_amount)}</TableCell>
                                        <TableCell>
                                            <div className="space-y-1">
                                                <p>{allocation.reference_no || '—'}</p>
                                                <p className="text-muted-foreground max-w-56 truncate text-xs" title={allocation.remarks ?? ''}>{allocation.remarks || 'No remarks'}</p>
                                                {allocation.void_reason && <p className="text-destructive text-xs">Void reason: {allocation.void_reason}</p>}
                                            </div>
                                        </TableCell>
                                        <TableCell><Badge variant={allocation.status === 'active' ? 'default' : 'secondary'}>{allocation.status}</Badge></TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                {canManage && allocation.status === 'active' && allocation.type !== 'transfer' && <>
                                                    <Button variant="outline" size="sm" onClick={() => openEdit(allocation)}><Pencil className="size-4" /></Button>
                                                </>}
                                                {canManage && allocation.status === 'active' && (
                                                    <Button variant="outline" size="sm" onClick={() => { voidForm.reset(); voidForm.clearErrors(); setVoidDialog(allocation); }}><Trash2 className="size-4" /></Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )) : <TableRow><TableCell colSpan={9} className="text-muted-foreground h-24 text-center">No fuel allocations found.</TableCell></TableRow>}
                            </TableBody>
                        </Table>
                        {allocations.links?.length > 3 && <div className="mt-4 flex flex-wrap gap-2">{allocations.links.map((link, i) => <Button key={`${link.label}-${i}`} variant={link.active ? 'default' : 'outline'} size="sm" disabled={!link.url} onClick={() => link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })}><span dangerouslySetInnerHTML={{ __html: link.label }} /></Button>)}</div>}
                    </CardContent>
                </Card>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit fuel allocation' : 'Add fuel allocation / transfer'}</DialogTitle>
                        <DialogDescription>Record regular allocation, or transfer/borrow fuel from one office to another. Transfers create paired debit/credit records.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitAllocation} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Record type *</Label>
                                <CustomComboBox
                                    items={[{ value: 'allocation', label: 'Regular allocation' }, { value: 'transfer', label: 'Office transfer / borrow' }]}
                                    value={form.data.type}
                                    placeholder="Select type"
                                    onSelect={(v) => form.setData('type', (v as 'allocation' | 'transfer') ?? 'allocation')}
                                    disabled={!!editing}
                                />
                                <InputError message={form.errors.type} />
                            </div>
                            {form.data.type === 'transfer' && (
                                <div className="space-y-2">
                                    <Label>Source office (galing) *</Label>
                                    <CustomComboBox
                                        items={offices.filter((o) => String(o.id) !== form.data.office_id).map((office) => ({ value: String(office.id), label: office.office_name }))}
                                        value={form.data.source_office_id}
                                        placeholder="Select source office"
                                        onSelect={(v) => form.setData('source_office_id', v ?? '')}
                                        disabled={!!editing}
                                    />
                                    <InputError message={form.errors.source_office_id} />
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label>{form.data.type === 'transfer' ? 'Receiving office (papunta) *' : 'Office *'}</Label>
                                <CustomComboBox
                                    items={offices.filter((o) => String(o.id) !== form.data.source_office_id).map((office) => ({ value: String(office.id), label: office.office_name }))}
                                    value={form.data.office_id}
                                    placeholder="Select office"
                                    onSelect={(v) => form.setData('office_id', v ?? '')}
                                    disabled={!!editing}
                                />
                                <InputError message={form.errors.office_id} />
                            </div>
                            <div className="space-y-2"><Label htmlFor="allocation_date">Allocation date *</Label><Input id="allocation_date" type="date" value={form.data.allocation_date} onChange={(e) => form.setData('allocation_date', e.target.value)} /><InputError message={form.errors.allocation_date} /></div>
                            <div className="space-y-2"><Label htmlFor="reference_no">Reference no.</Label><Input id="reference_no" value={form.data.reference_no} onChange={(e) => form.setData('reference_no', e.target.value)} /><InputError message={form.errors.reference_no} /></div>
                            <div className="space-y-2">
                                <Label>Fuel type</Label>
                                <CustomComboBox
                                    items={fuelTypes.map((ft) => ({ value: String(ft.id), label: ft.name }))}
                                    value={form.data.fuel_type_id}
                                    placeholder="Select fuel type"
                                    onSelect={(v) => {
                                        const selected = fuelTypes.find((ft) => String(ft.id) === v);
                                        form.setData((data) => ({ ...data, fuel_type_id: v ?? '', fuel_type: selected?.name ?? '' }));
                                    }}
                                />
                                <InputError message={form.errors.fuel_type_id ?? form.errors.fuel_type} />
                            </div>
                            <div className="space-y-2"><Label htmlFor="allocated_liters">Allocated liters</Label><Input id="allocated_liters" type="number" min="0" step="0.01" value={form.data.allocated_liters} onChange={(e) => form.setData('allocated_liters', e.target.value)} /><InputError message={form.errors.allocated_liters} /></div>
                            <div className="space-y-2"><Label htmlFor="allocated_amount">Allocated amount (PHP)</Label><Input id="allocated_amount" type="number" min="0" step="0.01" value={form.data.allocated_amount} onChange={(e) => form.setData('allocated_amount', e.target.value)} /><InputError message={form.errors.allocated_amount} /></div>
                        </div>
                        <div className="space-y-2"><Label htmlFor="remarks">Remarks</Label><Textarea id="remarks" rows={3} value={form.data.remarks} onChange={(e) => form.setData('remarks', e.target.value)} /><InputError message={form.errors.remarks} /></div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing}>{editing ? 'Save changes' : 'Add allocation'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!voidDialog} onOpenChange={(open) => !open && setVoidDialog(null)}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Void fuel allocation</DialogTitle><DialogDescription>Voided allocations stay in audit history but no longer count toward balances.</DialogDescription></DialogHeader>
                    <form onSubmit={submitVoid} className="space-y-4">
                        <div className="space-y-2"><Label htmlFor="void_reason">Reason *</Label><Textarea id="void_reason" rows={3} value={voidForm.data.void_reason} onChange={(e) => voidForm.setData('void_reason', e.target.value)} /><InputError message={voidForm.errors.void_reason} /></div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setVoidDialog(null)}>Cancel</Button>
                            <Button type="submit" variant="destructive" disabled={voidForm.processing}>Void allocation</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
