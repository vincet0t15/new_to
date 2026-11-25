import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
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
import employeesRoutes from '@/routes/employees';
import type { Employee } from '@/types/employee';
import type { PaginatedDataResponse } from '@/types/pagination';
import type { KeyboardEvent } from 'react';
import { DeleteEmployeeDialog } from './delete';

type EmployeesIndexProps = {
    employees: PaginatedDataResponse<Employee>;
    filters: {
        search?: string;
    };
};

/**
 * Render a name as "Last, First Middle Suffix" so the directory, which is
 * ordered by last name, reads alphabetically from top to bottom.
 */
function formatEmployeeName(employee: Employee): string {
    const given = [employee.first_name, employee.middle_name, employee.suffix]
        .filter(Boolean)
        .join(' ');

    return employee.last_name ? `${employee.last_name}, ${given}` : given;
}

export default function EmployeesIndex({
    employees,
    filters,
}: EmployeesIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const [selectedEmployee, setSelectedEmployee] = useState<Employee | null>(
        null,
    );
    const [deleteOpen, setDeleteOpen] = useState(false);

    const handleDelete = (employee: Employee) => {
        setSelectedEmployee(employee);
        setDeleteOpen(true);
    };

    const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        router.get(
            employeesRoutes.index().url,
            data.search ? { search: data.search } : undefined,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Employees" />

            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading
                    title="Employees"
                    description="Directory of municipal employees."
                />

                <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Can permission="employees.create">
                        <Button
                            onClick={() =>
                                router.get(employeesRoutes.create().url)
                            }
                        >
                            <Plus />
                            Add Employee
                        </Button>
                    </Can>

                    <div className="relative w-full sm:w-[280px]">
                        <Label htmlFor="search" className="sr-only">
                            Search
                        </Label>
                        <Input
                            id="search"
                            placeholder="Search by name, position, office, or contact..."
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
                                <TableHead>Position</TableHead>
                                <TableHead>Office</TableHead>
                                <TableHead>Employment Type</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {employees.data.length > 0 ? (
                                employees.data.map((employee) => (
                                    <TableRow key={employee.id}>
                                        <TableCell className="font-medium">
                                            {formatEmployeeName(employee)}
                                        </TableCell>
                                        <TableCell>
                                            {employee.position?.name ?? (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {employee.office?.office_name ?? (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {employee.employment_type?.name ?? (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {employee.contact_number ?? (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    employee.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {employee.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Can permission="employees.update">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                        onClick={() =>
                                                            router.get(
                                                                employeesRoutes.edit(
                                                                    employee,
                                                                ).url,
                                                            )
                                                        }
                                                        title="Edit employee"
                                                    >
                                                        <Pencil />
                                                    </Button>
                                                </Can>
                                                <Can permission="employees.delete">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                        onClick={() =>
                                                            handleDelete(
                                                                employee,
                                                            )
                                                        }
                                                        title="Delete employee"
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </Can>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No employees found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination data={employees} />

                {deleteOpen && selectedEmployee && (
                    <DeleteEmployeeDialog
                        isOpen={deleteOpen}
                        onClose={() => setDeleteOpen(false)}
                        employee={selectedEmployee}
                    />
                )}
            </div>
        </>
    );
}

EmployeesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Employees',
            href: employeesRoutes.index().url,
        },
    ],
};
