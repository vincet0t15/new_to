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
import signatoryGroupsRoutes from '@/routes/signatory-groups';
import type { OfficeSelectOption } from '@/types/office';
import type { PaginatedDataResponse } from '@/types/pagination';
import type {
    SignatoryGroup,
    SignatoryGroupEmployee,
    SignatoryGroupEmployeeOption,
} from '@/types/signatory-group';
import type { SelectOption } from '@/types/ui';
import type { KeyboardEvent } from 'react';
import { CreateSignatoryGroupDialog } from './create';
import { DeleteSignatoryGroupDialog } from './delete';
import { EditSignatoryGroupDialog } from './edit';

type SignatoryGroupsIndexProps = {
    signatoryGroups: PaginatedDataResponse<SignatoryGroup>;
    employees: SignatoryGroupEmployeeOption[];
    offices: OfficeSelectOption[];
    filters: {
        search?: string;
    };
};

export default function SignatoryGroupsIndex({
    signatoryGroups,
    employees,
    offices,
    filters,
}: SignatoryGroupsIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selected, setSelected] = useState<SignatoryGroup | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    // The picker labels a person by name and office, so two signers of the same
    // name can be told apart.
    const employeeOptions: SelectOption[] = employees.map((employee) => ({
        id: employee.id,
        name: employee.office_name
            ? `${employee.full_name} — ${employee.office_name}`
            : employee.full_name,
    }));

    const handleEdit = (signatoryGroup: SignatoryGroup) => {
        setSelected(signatoryGroup);
        setEditOpen(true);
    };

    const handleDelete = (signatoryGroup: SignatoryGroup) => {
        setSelected(signatoryGroup);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            signatoryGroupsRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const renderOffices = (groupOffices: OfficeSelectOption[]) => {
        if (groupOffices.length === 0) {
            return <span className="text-muted-foreground">—</span>;
        }

        return (
            <div className="max-w-[220px]">
                {groupOffices.map((office) => office.office_name).join(', ')}
            </div>
        );
    };

    const renderSigner = (
        employee: SignatoryGroupEmployee | null,
        title: string | null,
    ) => {
        if (!employee && !title) {
            return <span className="text-muted-foreground">—</span>;
        }

        return (
            <div className="space-y-0.5">
                {employee && <div>{employee.full_name}</div>}
                {title && (
                    <div className="text-xs text-muted-foreground">{title}</div>
                )}
            </div>
        );
    };

    return (
        <>
            <Head title="Travel Order Signatories" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Travel Order Signatories"
                    description="Reusable sets of the travel order's signatories. Name a group, pick its three signers, and tick the offices that print it, then reuse it instead of re-picking names on every order."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="signatory-groups.create">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Create Group
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[250px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search travel order signatories..."
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
                                <TableHead>Offices</TableHead>
                                <TableHead>
                                    Certified as to Availability of
                                    Appropriation
                                </TableHead>
                                <TableHead>Approved</TableHead>
                                <TableHead>OIC</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {signatoryGroups.data.length > 0 ? (
                                signatoryGroups.data.map((signatoryGroup) => (
                                    <TableRow key={signatoryGroup.id}>
                                        <TableCell className="align-top font-medium">
                                            {signatoryGroup.name}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            {renderOffices(
                                                signatoryGroup.offices,
                                            )}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            {renderSigner(
                                                signatoryGroup.certified_signatory,
                                                signatoryGroup.certified_signatory_title,
                                            )}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            {renderSigner(
                                                signatoryGroup.approved_signatory,
                                                signatoryGroup.approved_signatory_title,
                                            )}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            {renderSigner(
                                                signatoryGroup.oic_signatory,
                                                signatoryGroup.oic_signatory_title,
                                            )}
                                        </TableCell>
                                        <TableCell className="flex items-start justify-end gap-2">
                                            <Can permission="signatory-groups.update">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                    onClick={() =>
                                                        handleEdit(
                                                            signatoryGroup,
                                                        )
                                                    }
                                                    title="Edit signatory group"
                                                >
                                                    <Pencil />
                                                </Button>
                                            </Can>
                                            <Can permission="signatory-groups.delete">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() =>
                                                        handleDelete(
                                                            signatoryGroup,
                                                        )
                                                    }
                                                    title="Delete signatory group"
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
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No signatory groups found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={signatoryGroups} />

                {createOpen && (
                    <CreateSignatoryGroupDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                        employeeOptions={employeeOptions}
                        offices={offices}
                    />
                )}

                {editOpen && selected && (
                    <EditSignatoryGroupDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        signatoryGroup={selected}
                        employeeOptions={employeeOptions}
                        offices={offices}
                    />
                )}

                {deleteOpen && selected && (
                    <DeleteSignatoryGroupDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        signatoryGroup={selected}
                    />
                )}
            </div>
        </>
    );
}

SignatoryGroupsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/admin/settings',
        },
        {
            title: 'Travel Order Signatories',
            href: signatoryGroupsRoutes.index().url,
        },
    ],
};
