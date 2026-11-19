import { Badge } from '@/components/ui/badge';
import type {
    TravelOrderDeliveryStatus,
    TravelOrderStatus,
    TripTicketStatus,
} from '@/types/travel-order';

const statusLabels: Record<TravelOrderStatus, string> = {
    draft: 'Draft',
    submitted: 'In progress',
    approved: 'Approved',
    returned: 'Returned for revision',
    cancelled: 'Cancelled',
    completed: 'Completed',
    rejected: 'Rejected',
};

const statusClasses: Record<TravelOrderStatus, string> = {
    draft: 'border-slate-300 text-slate-700 dark:text-slate-200',
    submitted:
        'border-blue-300 bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200',
    approved:
        'border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
    returned:
        'border-amber-300 bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
    cancelled:
        'border-red-300 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-200',
    completed:
        'border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
    rejected:
        'border-red-300 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-200',
};

export function TravelOrderStatusBadge({
    status,
}: {
    status: TravelOrderStatus;
}) {
    return (
        <Badge variant="outline" className={statusClasses[status]}>
            {statusLabels[status]}
        </Badge>
    );
}

const deliveryLabels: Record<TravelOrderDeliveryStatus, string> = {
    pending: 'Waiting',
    received: 'Received',
    acted: 'Acted',
    closed: 'Closed',
};

export function DeliveryStatusBadge({
    status,
}: {
    status: TravelOrderDeliveryStatus;
}) {
    return <Badge variant="outline">{deliveryLabels[status] ?? status}</Badge>;
}

const tripTicketLabels: Record<TripTicketStatus, string> = {
    issued: 'Issued',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const tripTicketClasses: Record<TripTicketStatus, string> = {
    issued: 'border-blue-300 bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200',
    completed:
        'border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
    cancelled:
        'border-red-300 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-200',
};

export function TripTicketStatusBadge({
    status,
}: {
    status: TripTicketStatus;
}) {
    return (
        <Badge variant="outline" className={tripTicketClasses[status]}>
            {tripTicketLabels[status] ?? status}
        </Badge>
    );
}
