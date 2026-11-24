import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import adminTravelOrders from '@/routes/admin/travel-orders';
import designations from '@/routes/designations';
import type { DesignationImpact, OfficeDesignation } from '@/types/workflow';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    designation: OfficeDesignation;
};

export function DesignationImpactDialog({
    isOpen,
    onClose,
    designation,
}: Props) {
    const [impact, setImpact] = useState<DesignationImpact | null>(null);
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [toUserId, setToUserId] = useState('');
    const [remarks, setRemarks] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const errors = usePage().props.errors as Record<string, string | undefined>;

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        let cancelled = false;
        setLoading(true);
        setLoadError(null);
        setImpact(null);
        setToUserId('');

        fetch(designations.impact(designation).url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Unable to load the impact preview.');
                }

                return (await response.json()) as DesignationImpact;
            })
            .then((payload) => {
                if (!cancelled) {
                    setImpact(payload);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setLoadError('Unable to load the impact preview.');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [isOpen, designation.id]);

    const submit = () => {
        if (!impact || toUserId === '') {
            return;
        }

        setSubmitting(true);
        router.post(
            adminTravelOrders.reassignBulk().url,
            {
                travel_order_ids: impact.orders.map((order) => order.id),
                to_user_id: Number(toUserId),
                remarks: remarks === '' ? null : remarks,
            },
            {
                preserveScroll: true,
                onFinish: () => setSubmitting(false),
                onSuccess: () => onClose(),
            },
        );
    };

    const candidates =
        impact?.candidates.map((candidate) => ({
            value: String(candidate.id),
            label: `${candidate.name} (${candidate.username})`,
        })) ?? [];

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Designation impact</DialogTitle>
                    <DialogDescription>
                        {designation.office.office_name} ·{' '}
                        {designation.step_type.name} ·{' '}
                        {designation.employee.full_name}
                    </DialogDescription>
                </DialogHeader>

                <div className="max-h-[55vh] overflow-y-auto">
                    {loading ? (
                        <div className="flex items-center justify-center gap-2 py-10 text-sm text-muted-foreground">
                            <Spinner /> Loading impact…
                        </div>
                    ) : loadError !== null ? (
                        <p className="py-10 text-center text-sm text-destructive">
                            {loadError}
                        </p>
                    ) : impact === null ? (
                        <p className="py-10 text-center text-sm text-muted-foreground">
                            No preview available.
                        </p>
                    ) : impact.orders.length === 0 ? (
                        <p className="py-10 text-center text-sm text-muted-foreground">
                            No submitted orders are currently waiting on this
                            holder. Changing the designation affects future
                            routing only.
                        </p>
                    ) : (
                        <>
                            <p className="mb-3 text-sm text-muted-foreground">
                                {impact.orders.length} in-flight travel order
                                {impact.orders.length === 1 ? '' : 's'} still
                                sit with this holder. Choose a replacement to
                                move those open deliveries without advancing the
                                workflow.
                            </p>
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead>Travel order</TableHead>
                                        <TableHead>Step</TableHead>
                                        <TableHead>Holder</TableHead>
                                        <TableHead className="text-right">
                                            Waiting
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {impact.orders.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell>
                                                <span className="block font-medium">
                                                    {order.control_no ??
                                                        order.to_number ??
                                                        `#${order.id}`}
                                                </span>
                                                <span className="block max-w-[16rem] truncate text-xs text-muted-foreground">
                                                    {order.purpose ?? '—'}
                                                </span>
                                            </TableCell>
                                            <TableCell>{order.step}</TableCell>
                                            <TableCell>
                                                {order.holder}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {order.age_days}d
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </>
                    )}
                </div>

                <div className="space-y-2">
                    {candidates.length > 0 && (
                        <Combobox
                            value={toUserId}
                            onValueChange={setToUserId}
                            options={candidates}
                            placeholder="Choose a replacement holder"
                            searchPlaceholder="Search people…"
                            emptyMessage="No candidates found."
                        />
                    )}
                    <textarea
                        value={remarks}
                        onChange={(event) => setRemarks(event.target.value)}
                        placeholder="Reason for reassignment (optional)"
                        rows={2}
                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    {errors.action && (
                        <p className="text-sm text-destructive">
                            {errors.action}
                        </p>
                    )}
                </div>

                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="outline">
                            Close
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        disabled={
                            submitting ||
                            impact === null ||
                            impact.orders.length === 0 ||
                            toUserId === ''
                        }
                        onClick={submit}
                    >
                        {submitting && <Spinner />}
                        Reassign affected orders
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
