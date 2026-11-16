import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import employees from '@/routes/employees';
import type { Employee } from '@/types/employee';
import type { EmploymentType } from '@/types/employment-type';
import type { Office } from '@/types/office';
import type { Position } from '@/types/position';
import { EmployeeForm } from './employee-form';

type EditEmployeeProps = {
    employee: Employee;
    offices: Pick<Office, 'id' | 'office_name'>[];
    positions: Pick<Position, 'id' | 'name'>[];
    employmentTypes: Pick<EmploymentType, 'id' | 'name'>[];
};

export default function EditEmployee({
    employee,
    offices,
    positions,
    employmentTypes,
}: EditEmployeeProps) {
    return (
        <>
            <Head title="Edit Employee" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-start gap-4">
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label="Back to employees"
                        onClick={() => router.get(employees.index().url)}
                    >
                        <ArrowLeft />
                    </Button>
                    <Heading
                        title="Edit Employee"
                        description={`Update the details for ${employee.full_name}.`}
                    />
                </div>

                <EmployeeForm
                    employee={employee}
                    offices={offices}
                    positions={positions}
                    employmentTypes={employmentTypes}
                />
            </div>
        </>
    );
}

EditEmployee.layout = {
    breadcrumbs: [
        {
            title: 'Employees',
            href: employees.index().url,
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
