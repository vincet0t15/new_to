import { RouteProgress } from '@/components/travel-orders/route-progress';
import { TravelOrderStatusBadge } from '@/components/travel-orders/status-badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type {
    TravelOrder,
    TravelOrderRouteStep,
    TravelOrderStatus,
} from '@/types/travel-order';

type CurrentStep = {
    label: string;
    office: string | null;
    is_signatory: boolean;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    travelOrder: TravelOrder;
    route: TravelOrderRouteStep[];
    currentStep: CurrentStep | null;
};

function routeSummary(
    status: TravelOrderStatus,
    currentStep: CurrentStep | null,
): { title: string; detail: string } {
    if (status === 'submitted' && currentStep) {
        return {
            title: `Currently processing at ${currentStep.label}`,
            detail: [
                currentStep.office,
                currentStep.is_signatory
                    ? 'Waiting for a signature.'
                    : 'Waiting for a receipt.',
            ]
                .filter(Boolean)
                .join(' · '),
        };
    }

    if (status === 'submitted') {
        return {
            title: 'In progress',
            detail: 'Waiting for the next step in the route.',
        };
    }

    if (status === 'draft') {
        return {
            title: 'Not yet submitted',
            detail: 'This is the route the order will take once you submit it.',
        };
    }

    if (status === 'returned') {
        return {
            title: 'Returned for revision',
            detail: 'Update the order and resubmit it to continue the route.',
        };
    }

    if (status === 'approved' || status === 'completed') {
        return {
            title: 'Approval route complete',
            detail: 'Every step in the route has been cleared.',
        };
    }

    if (status === 'cancelled' || status === 'rejected') {
        return {
            title: 'Route stopped',
            detail: 'This order is no longer moving through the approval route.',
        };
    }

    return {
        title: 'Approval route',
        detail: 'The steps this order follows.',
    };
}

export function DocumentStatusDialog({
    open,
    onOpenChange,
    travelOrder,
    route,
    currentStep,
}: Props) {
    const headline = routeSummary(travelOrder.status, currentStep);
    const documentNumber =
        travelOrder.control_no ?? travelOrder.to_number ?? `#${travelOrder.id}`;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Document status and route</DialogTitle>
                    <DialogDescription>
                        {documentNumber}
                        {travelOrder.purpose ? ` · ${travelOrder.purpose}` : ''}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="rounded-lg border bg-muted/40 p-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <TravelOrderStatusBadge
                                status={travelOrder.status}
                            />
                            <span className="text-xs text-muted-foreground">
                                Control No. {documentNumber}
                            </span>
                        </div>
                        <p className="mt-2 text-sm font-medium">
                            {headline.title}
                        </p>
                        {headline.detail && (
                            <p className="text-xs text-muted-foreground">
                                {headline.detail}
                            </p>
                        )}
                    </div>

                    <RouteProgress steps={route} />
                </div>
            </DialogContent>
        </Dialog>
    );
}
