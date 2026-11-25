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
import itinerarySignatoryGroupsRoutes from '@/routes/itinerary-signatory-groups';
import type {
    ItinerarySignatoryGroup,
    ItinerarySignatoryGroupEmployee,
    ItinerarySignatoryGroupEmployeeOption,
} from '@/types/itinerary-signatory-group';
import type { OfficeSelectOption } from '@/types/office';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { SelectOption } from '@/types/ui';
import type { KeyboardEvent } from 'react';
import { CreateItinerarySignatoryGroupDialog } from './create';
import { DeleteItinerarySignatoryGroupDialog } from './delete';
import { EditItinerarySignatoryGroupDialog } from './edit';

type ItinerarySignatoryGroupsIndexProps = {
    itinerarySignatoryGroups: PaginatedDataResponse<ItinerarySignatoryGroup>;
    employees: ItinerarySignatoryGroupEmployeeOption[];
    offices: OfficeSelectOption[];
    filters: {
        search?: string;
    };
};

export default function ItinerarySignatoryGroupsIndex({
    itinerarySignatoryGroups,
    employees,
    offices,
    filters,
}: ItinerarySignatoryGroupsIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [selected, setSelected] = useState<ItinerarySignatoryGroup | null>(
        null,
    );
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

    const handleEdit = (itinerarySignatoryGroup: ItinerarySignatoryGroup) => {
        setSelected(itinerarySignatoryGroup);
        setEditOpen(true);
    };

    const handleDelete = (itinerarySignatoryGroup: ItinerarySignatoryGroup) => {
        setSelected(itinerarySignatoryGroup);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            itinerarySignatoryGroupsRoutes.index().url,
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
        employee: ItinerarySignatoryGroupEmployee | null,
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
            <Head title="Itinerary Signatory Groups" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Itinerary Signatory Groups"
                    description="Reusable sets of the Itinerary of Travel's signatories. Name a group, pick its certified and approved signers, and tick the offices that print it, then reuse it instead of re-picking names on every order."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="itinerary-signatory-groups.create">
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
                            placeholder="Search itinerary signatory groups..."
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
                                <TableHead>Certified/Reviewed By</TableHead>
                                <TableHead>Approved By</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {itinerarySignatoryGroups.data.length > 0 ? (
                                itinerarySignatoryGroups.data.map(
                                    (itinerarySignatoryGroup) => (
                                        <TableRow
                                            key={itinerarySignatoryGroup.id}
                                        >
                                            <TableCell className="align-top font-medium">
                                                {itinerarySignatoryGroup.name}
                                            </TableCell>
                                            <TableCell className="align-top">
                                                {renderOffices(
                                                    itinerarySignatoryGroup.offices,
                                                )}
                                            </TableCell>
                                            <TableCell className="align-top">
                                                {renderSigner(
                                                    itinerarySignatoryGroup.certified_employee,
                                                    itinerarySignatoryGroup.certified_title,
                                                )}
                                            </TableCell>
                                            <TableCell className="align-top">
                                                {renderSigner(
                                                    itinerarySignatoryGroup.approved_employee,
                                                    itinerarySignatoryGroup.approved_title,
                                                )}
                                            </TableCell>
                                            <TableCell className="flex items-start justify-end gap-2">
                                                <Can permission="itinerary-signatory-groups.update">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                        onClick={() =>
                                                            handleEdit(
                                                                itinerarySignatoryGroup,
                                                            )
                                                        }
                                                        title="Edit itinerary signatory group"
                                                    >
                                                        <Pencil />
                                                    </Button>
                                                </Can>
                                                <Can permission="itinerary-signatory-groups.delete">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                        onClick={() =>
                                                            handleDelete(
                                                                itinerarySignatoryGroup,
                                                            )
                                                        }
                                                        title="Delete itinerary signatory group"
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
                                        No itinerary signatory groups found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={itinerarySignatoryGroups} />

                {createOpen && (
                    <CreateItinerarySignatoryGroupDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                        employeeOptions={employeeOptions}
                        offices={offices}
                    />
                )}

                {editOpen && selected && (
                    <EditItinerarySignatoryGroupDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        itinerarySignatoryGroup={selected}
                        employeeOptions={employeeOptions}
                        offices={offices}
                    />
                )}

                {deleteOpen && selected && (
                    <DeleteItinerarySignatoryGroupDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        itinerarySignatoryGroup={selected}
                    />
                )}
            </div>
        </>
    );
}

ItinerarySignatoryGroupsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/admin/settings',
        },
        {
            title: 'Itinerary Signatory Groups',
            href: itinerarySignatoryGroupsRoutes.index().url,
        },
    ],
};
