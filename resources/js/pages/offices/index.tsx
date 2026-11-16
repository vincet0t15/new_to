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
import officesRoutes from '@/routes/offices';
import type { Office } from '@/types/office';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { WorkflowOption } from '@/types/workflow';
import type { KeyboardEvent } from 'react';
import { CreateOfficeDialog } from './create';
import { DeleteOfficeDialog } from './delete';
import { EditOfficeDialog } from './edit';

type OfficesIndexProps = {
    offices: PaginatedDataResponse<Office>;
    filters: {
        search?: string;
    };
    workflows: WorkflowOption[];
};

export default function OfficesIndex({
    offices,
    filters,
    workflows,
}: OfficesIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selectedOffice, setSelectedOffice] = useState<Office | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleEdit = (office: Office) => {
        setSelectedOffice(office);
        setEditOpen(true);
    };

    const handleDelete = (office: Office) => {
        setSelectedOffice(office);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            officesRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Offices" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Office Management"
                    description="Create, update, and manage your office records."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="offices.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Office
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search offices..."
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
                                <TableHead>Office Name</TableHead>
                                <TableHead>Office Code</TableHead>
                                <TableHead>Approval Workflow</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {offices.data.length > 0 ? (
                                offices.data.map((office) => (
                                    <TableRow key={office.id}>
                                        <TableCell className="font-medium">
                                            {office.office_name}
                                        </TableCell>
                                        <TableCell>
                                            {office.office_code || (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {office.workflow ? (
                                                <span className="inline-flex items-center gap-1">
                                                    {office.workflow.name}
                                                    {!office.workflow
                                                        .is_active && (
                                                        <span className="text-xs text-muted-foreground">
                                                            (inactive)
                                                        </span>
                                                    )}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Default
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Can permission="offices.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    onClick={() =>
                                                        handleEdit(office)
                                                    }
                                                    title="Edit office"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="offices.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() =>
                                                        handleDelete(office)
                                                    }
                                                    title="Delete office"
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
                                        No offices found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={offices} />

                {createOpen && (
                    <CreateOfficeDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                        workflows={workflows}
                    />
                )}

                {editOpen && selectedOffice && (
                    <EditOfficeDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        office={selectedOffice}
                        workflows={workflows}
                    />
                )}

                {deleteOpen && selectedOffice && (
                    <DeleteOfficeDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        office={selectedOffice}
                    />
                )}
            </div>
        </>
    );
}

OfficesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Offices',
            href: officesRoutes.index().url,
        },
    ],
};
