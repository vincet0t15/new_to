import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import departmentHead from '@/routes/department-head';

export default function DepartmentHeadDashboard() {
    return (
        <>
            <Head title="Department Head Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Department Head Dashboard"
                    description="Review travel orders waiting at your office's approval step."
                />

                <div className="relative min-h-[60vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

DepartmentHeadDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Department Head Dashboard',
            href: departmentHead.dashboard().url,
        },
    ],
};
