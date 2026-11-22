import { Head, router } from '@inertiajs/react';
import { CheckCircle2, FilePenLine, Plus, RotateCcw, Send } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import travelOrdersRoutes from '@/routes/travel-orders';
import employee from '@/routes/employee';
import type { PersonalDashboardStats } from '@/types';

type Props = {
    stats: PersonalDashboardStats;
};

export default function EmployeeDashboard({ stats }: Props) {
    return (
        <>
            <Head title="Employee Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Employee Dashboard"
                        description="Create and track your travel orders from one workspace."
                    />

                    <Can permission="travel-orders.create">
                        <Button
                            onClick={() =>
                                router.visit(travelOrdersRoutes.create().url)
                            }
                        >
                            <Plus />
                            New Travel Order
                        </Button>
                    </Can>
                </div>

                <section aria-labelledby="personal-stats-heading">
                    <h2 id="personal-stats-heading" className="sr-only">
                        Your travel order summary
                    </h2>
                    <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <StatCard
                            label="Drafts"
                            value={stats.draft}
                            description="Still editable"
                            href={
                                travelOrdersRoutes.index({
                                    query: { status: 'draft' },
                                }).url
                            }
                            icon={FilePenLine}
                        />
                        <StatCard
                            label="In progress"
                            value={stats.submitted}
                            description="Moving through approval"
                            href={
                                travelOrdersRoutes.index({
                                    query: { status: 'submitted' },
                                }).url
                            }
                            icon={Send}
                            tone="warning"
                        />
                        <StatCard
                            label="Returned"
                            value={stats.returned}
                            description="Needs your revision"
                            href={travelOrdersRoutes.returned().url}
                            icon={RotateCcw}
                            tone="warning"
                        />
                        <StatCard
                            label="Approved"
                            value={stats.approved}
                            description="Finalized orders"
                            href={
                                travelOrdersRoutes.index({
                                    query: { status: 'approved' },
                                }).url
                            }
                            icon={CheckCircle2}
                            tone="success"
                        />
                    </div>
                </section>

                <Can permission="travel-orders.view">
                    <section aria-labelledby="personal-shortcuts-heading">
                        <h2
                            id="personal-shortcuts-heading"
                            className="mb-3 text-sm font-semibold"
                        >
                            Quick links
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Card className="gap-2 p-4">
                                <CardTitle className="text-base">
                                    My Travel Orders
                                </CardTitle>
                                <CardDescription>
                                    Follow every order you filed from draft to
                                    approval.
                                </CardDescription>
                                <Button
                                    variant="link"
                                    className="w-fit px-0"
                                    onClick={() =>
                                        router.visit(
                                            travelOrdersRoutes.index().url,
                                        )
                                    }
                                >
                                    Open my orders
                                </Button>
                            </Card>

                            <Card className="gap-2 p-4">
                                <CardTitle className="text-base">
                                    Returned for Revision
                                </CardTitle>
                                <CardDescription>
                                    Complete the items your approvers sent back.
                                </CardDescription>
                                <Button
                                    variant="link"
                                    className="w-fit px-0"
                                    onClick={() =>
                                        router.visit(
                                            travelOrdersRoutes.returned().url,
                                        )
                                    }
                                >
                                    Review returned
                                </Button>
                            </Card>
                        </div>
                    </section>
                </Can>
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
