import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import employee from '@/routes/employee';

export default function EmployeeDashboard() {
    return (
        <>
            <Head title="Employee Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Employee Dashboard"
                    description="Create and track your travel orders from one workspace."
                />

                <div className="relative min-h-[60vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

EmployeeDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Employee Dashboard',
            href: employee.dashboard().url,
        },
    ],
};
