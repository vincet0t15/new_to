import { Head, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import designationsRoutes from '@/routes/designations';
import type {
    DesignationEmployeeOption,
    OfficeDesignation,
} from '@/types/workflow';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { Office } from '@/types/office';
import type { WorkflowStepType } from '@/types/workflow';
import { CreateDesignationDialog } from './create';
import { DeleteDesignationDialog } from './delete';
import { EditDesignationDialog } from './edit';
import { DesignationImpactDialog } from './impact';

type Props = {
    designations: PaginatedDataResponse<OfficeDesignation>;
    filters: {
        office_id?: number | null;
        workflow_step_type_id?: number | null;
    };
    offices: Pick<Office, 'id' | 'office_name'>[];
    stepTypes: WorkflowStepType[];
    employees: DesignationEmployeeOption[];
};

export default function DesignationsIndex({
    designations,
    filters,
    offices,
    stepTypes,
    employees,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [selected, setSelected] = useState<OfficeDesignation | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [impactOpen, setImpactOpen] = useState(false);

    const [officeFilter, setOfficeFilter] = useState<string>(
        filters.office_id ? String(filters.office_id) : 'all',
    );
    const [stepFilter, setStepFilter] = useState<string>(
        filters.workflow_step_type_id
            ? String(filters.workflow_step_type_id)
            : 'all',
    );

    const applyFilters = () => {
        router.get(
            designationsRoutes.index().url,
            {
                office_id: officeFilter !== 'all' ? officeFilter : undefined,
                workflow_step_type_id:
                    stepFilter !== 'all' ? stepFilter : undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Designations" />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Office Designations"
                    description="Who answers each step for each office. Any active employee may hold a step, including someone acting from another office. When several people hold one step, the first to act claims it."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="designations.manage">
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus />
                            Add Designation
                        </Button>
                    </Can>
                    <div className="flex flex-wrap items-center gap-2">
                        <Combobox
                            ariaLabel="Filter by office"
                            value={officeFilter}
                            onValueChange={setOfficeFilter}
                            options={[
                                { value: 'all', label: 'All offices' },
                                ...offices.map((office) => ({
                                    value: String(office.id),
                                    label: office.office_name,
                                })),
                            ]}
                            placeholder="All offices"
                            searchPlaceholder="Search office..."
                            emptyMessage="No offices found."
                            className="w-[200px]"
                        />
                        <Combobox
                            ariaLabel="Filter by step type"
                            value={stepFilter}
                            onValueChange={setStepFilter}
                            options={[
                                { value: 'all', label: 'All step types' },
                                ...stepTypes.map((type) => ({
                                    value: String(type.id),
                                    label: type.name,
                                })),
                            ]}
                            placeholder="All step types"
                            searchPlaceholder="Search step type..."
                            emptyMessage="No step types found."
                            className="w-[200px]"
                        />
                        <Button variant="outline" onClick={applyFilters}>
                            <Search className="size-4" />
                            Filter
                        </Button>
                    </div>
                </div>

                <div className="w-full overflow-hidden rounded-sm border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Office</TableHead>
                                <TableHead>Step Type</TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Account</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {designations.data.length > 0 ? (
                                designations.data.map((designation) => (
                                    <TableRow key={designation.id}>
                                        <TableCell className="font-medium">
                                            {designation.office.office_name}
                                        </TableCell>
                                        <TableCell>
                                            {designation.step_type.name}
                                        </TableCell>
                                        <TableCell>
                                            <span className="block">
                                                {designation.employee.full_name}
                                            </span>
                                            {designation.employee.office_id !==
                                                designation.office_id && (
                                                <span className="block text-xs text-muted-foreground">
                                                    Acting from{' '}
                                                    {designation.employee.office
                                                        ?.office_name ??
                                                        'another office'}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    designation.is_deputy
                                                        ? 'outline'
                                                        : 'default'
                                                }
                                            >
                                                {designation.is_deputy
                                                    ? 'Deputy'
                                                    : 'Primary'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {designation.employee.user
                                                ? 'Yes'
                                                : 'No'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    designation.is_active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {designation.is_active
                                                    ? 'Active'
                                                    : 'Ended'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-emerald-600"
                                                onClick={() => {
                                                    setSelected(designation);
                                                    setImpactOpen(true);
                                                }}
                                                title="Review impact"
                                            >
                                                <Eye />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-blue-600"
                                                onClick={() => {
                                                    setSelected(designation);
                                                    setEditOpen(true);
                                                }}
                                                title="Edit"
                                            >
                                                <Pencil />
                                            </Button>
                                            {designation.is_active && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600"
                                                    onClick={() => {
                                                        setSelected(
                                                            designation,
                                                        );
                                                        setDeleteOpen(true);
                                                    }}
                                                    title="End designation"
                                                >
                                                    <Trash2 />
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No designations found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={designations} />

                {createOpen && (
                    <CreateDesignationDialog
                        isOpen={createOpen}
                        onClose={() => setCreateOpen(false)}
                        offices={offices}
                        stepTypes={stepTypes}
                        employees={employees}
                        initialOfficeId={filters.office_id ?? null}
                        initialStepTypeId={
                            filters.workflow_step_type_id ?? null
                        }
                    />
                )}
                {editOpen && selected && (
                    <EditDesignationDialog
                        isOpen={editOpen}
                        onClose={() => setEditOpen(false)}
                        designation={selected}
                        offices={offices}
                        stepTypes={stepTypes}
                        employees={employees}
                    />
                )}
                {deleteOpen && selected && (
                    <DeleteDesignationDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        designation={selected}
                    />
                )}
                {impactOpen && selected && (
                    <DesignationImpactDialog
                        isOpen={impactOpen}
                        onClose={() => setImpactOpen(false)}
                        designation={selected}
                    />
                )}
            </div>
        </>
    );
}

DesignationsIndex.layout = {
    breadcrumbs: [
        { title: 'Designations', href: designationsRoutes.index().url },
    ],
};
