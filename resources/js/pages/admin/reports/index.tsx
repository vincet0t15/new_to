import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

type Props = {
    summary: { total: number; by_status: Record<string, number> };
    by_office: { office_name: string; total: number }[];
    offices: { id: number; office_name: string }[];
    filters: {
        search?: string;
        status?: string;
        office_id?: string;
        date_from?: string;
        date_to?: string;
    };
};

export default function AdminReportsIndex({ summary, by_office }: Props) {
    return (
        <>
            <Head title="Reports" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Travel Order Reports"
                    description="Summary of travel orders across the organization."
                />
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-xl border p-4">
                        <p className="text-sm text-muted-foreground">Total</p>
                        <p className="text-2xl font-semibold">
                            {summary.total}
                        </p>
                    </div>
                    {Object.entries(summary.by_status).map(
                        ([status, count]) => (
                            <div key={status} className="rounded-xl border p-4">
                                <p className="text-sm text-muted-foreground">
                                    {status}
                                </p>
                                <p className="text-2xl font-semibold">
                                    {count}
                                </p>
                            </div>
                        ),
                    )}
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h3 className="text-base font-semibold">By office</h3>
                    <ul className="space-y-1 text-sm">
                        {by_office.map((row) => (
                            <li
                                key={row.office_name}
                                className="flex justify-between"
                            >
                                <span>{row.office_name}</span>
                                <span className="font-medium">{row.total}</span>
                            </li>
                        ))}
                    </ul>
                </section>

                <div>
                    <Button asChild>
                        <a href={admin.reports.export().url}>Export CSV</a>
                    </Button>
                </div>
            </div>
        </>
    );
}

AdminReportsIndex.layout = {
    breadcrumbs: [{ title: 'Reports', href: admin.reports.index().url }],
};
