import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, FileClock, FileText, Fuel } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';

type Props = {
    stats: { draft: number; submitted: number; approved: number; completed: number; cancelled: number };
    fuelAlerts: Array<{
        office_id: number;
        office_name: string;
        fuel_type: string;
        remaining_liters: number;
        remaining_amount: number;
    }>;
    fuelThresholds: { low_balance_liters: number; low_balance_amount: number };
};

function money(value: number) {
    return `PHP ${Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function liters(value: number) {
    return `${Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} L`;
}

export default function AdminDashboard({ stats, fuelAlerts, fuelThresholds }: Props) {
    const cards = [
        { label: 'Draft', value: stats.draft, icon: FileText },
        { label: 'Submitted / In Progress', value: stats.submitted, icon: FileClock },
        { label: 'Approved', value: stats.approved, icon: CheckCircle2 },
        { label: 'Completed', value: stats.completed, icon: CheckCircle2 },
    ];

    return (
        <>
            <Head title="Admin Dashboard" />
            <div className="flex h-full w-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 sm:p-6">
                <Heading title="Admin Dashboard" description="Manage the organization, travel orders, and fuel monitoring." />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader className="pb-2">
                                <CardDescription className="flex items-center gap-2"><card.icon className="size-4" />{card.label}</CardDescription>
                                <CardTitle className="text-3xl tabular-nums">{card.value}</CardTitle>
                            </CardHeader>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base"><Fuel className="size-4" />Fuel balance alerts</CardTitle>
                        <CardDescription>
                            Low/negative balances by office + fuel type. Thresholds: {liters(fuelThresholds.low_balance_liters)} or {money(fuelThresholds.low_balance_amount)}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {fuelAlerts.length > 0 ? fuelAlerts.map((alert) => {
                            const negative = alert.remaining_liters < 0 || alert.remaining_amount < 0;
                            return (
                                <div key={`${alert.office_id}-${alert.fuel_type}`} className="flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="font-medium">{alert.office_name}</p>
                                        <p className="text-muted-foreground text-sm">{alert.fuel_type}</p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Badge variant={negative ? 'destructive' : 'secondary'} className="gap-1">
                                            <AlertTriangle className="size-3" />
                                            {negative ? 'Negative' : 'Low'}
                                        </Badge>
                                        <div className="text-right text-sm">
                                            <div className={negative ? 'text-destructive font-medium' : ''}>{liters(alert.remaining_liters)}</div>
                                            <div className="text-muted-foreground">{money(alert.remaining_amount)}</div>
                                        </div>
                                        <Link href={admin.fuelMonitoring.index({ query: { office_id: String(alert.office_id) } }).url} className="text-primary text-sm underline-offset-2 hover:underline">View</Link>
                                    </div>
                                </div>
                            );
                        }) : (
                            <p className="text-muted-foreground text-sm">No low or negative fuel balances.</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Admin Dashboard',
            href: admin.dashboard().url,
        },
    ],
};
