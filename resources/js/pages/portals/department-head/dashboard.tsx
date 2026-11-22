import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Inbox, RotateCcw } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import departmentHead from '@/routes/department-head';
import type { ProcessingDashboardStats } from '@/types';

type Props = {
    stats: ProcessingDashboardStats;
};

export default function DepartmentHeadDashboard({ stats }: Props) {
    return (
        <>
            <Head title="Department Head Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Department Head Dashboard"
                        description="Review travel orders waiting at your office's approval step."
                    />

                    <Can permission="travel-orders.view">
                        <Button
                            onClick={() =>
                                router.visit(departmentHead.inbox().url)
                            }
                        >
                            <Inbox />
                            Open approval inbox
                        </Button>
                    </Can>
                </div>

                <section aria-labelledby="department-stats-heading">
                    <h2 id="department-stats-heading" className="sr-only">
                        Your approval summary
                    </h2>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <StatCard
                            label="Pending"
                            value={stats.pending}
                            description="Waiting for your action"
                            href={departmentHead.inbox().url}
                            icon={Inbox}
                            tone="warning"
                        />
                        <StatCard
                            label="Processed"
                            value={stats.processed}
                            description="Orders you acted on"
                            href={departmentHead.history().url}
                            icon={CheckCircle2}
                            tone="success"
                        />
                        <StatCard
                            label="Returned"
                            value={stats.returned}
                            description="Sent back for revision"
                            href={departmentHead.history().url}
                            icon={RotateCcw}
                        />
                    </div>
                </section>

                <Can permission="travel-orders.view">
                    <section aria-labelledby="department-shortcuts-heading">
                        <h2
                            id="department-shortcuts-heading"
                            className="mb-3 text-sm font-semibold"
                        >
                            Quick links
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Card className="gap-2 p-4">
                                <CardTitle className="text-base">
                                    Approval Inbox
                                </CardTitle>
                                <CardDescription>
                                    Orders currently assigned to your office.
                                </CardDescription>
                                <Button
                                    variant="link"
                                    className="w-fit px-0"
                                    onClick={() =>
                                        router.visit(departmentHead.inbox().url)
                                    }
                                >
                                    Open inbox
                                </Button>
                            </Card>

                            <Card className="gap-2 p-4">
                                <CardTitle className="text-base">
                                    Processing History
                                </CardTitle>
                                <CardDescription>
                                    Every order that passed through your desk.
                                </CardDescription>
                                <Button
                                    variant="link"
                                    className="w-fit px-0"
                                    onClick={() =>
                                        router.visit(
                                            departmentHead.history().url,
                                        )
                                    }
                                >
                                    View history
                                </Button>
                            </Card>
                        </div>
                    </section>
                </Can>
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
