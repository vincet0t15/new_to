import { Head, Link, router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import notifications from '@/routes/notifications';
import type { NotificationInboxItem } from '@/types/notification';
import type { PaginatedDataResponse } from '@/types/pagination';

type Status = 'all' | 'unread' | 'read';

type Props = {
    notificationItems: PaginatedDataResponse<NotificationInboxItem>;
    filters: { status: Status };
    counts: { all: number; unread: number; read: number };
};

const FILTERS: { key: Status; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'unread', label: 'Unread' },
    { key: 'read', label: 'Read' },
];

function formatDateTime(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function kindLabel(kind: string): string {
    if (kind === 'returned') {
        return 'Returned';
    }

    if (kind === 'routed') {
        return 'Routed';
    }

    return 'Notice';
}

function kindVariant(
    kind: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (kind === 'returned') {
        return 'destructive';
    }

    if (kind === 'routed') {
        return 'secondary';
    }

    return 'outline';
}

export default function NotificationsIndex({
    notificationItems,
    filters,
    counts,
}: Props) {
    return (
        <>
            <Head title="Inbox" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Inbox"
                    description="Travel order updates addressed to your account."
                />

                <div className="flex flex-wrap items-center gap-2">
                    {FILTERS.map((filter) => (
                        <Button
                            key={filter.key}
                            size="sm"
                            variant={
                                filters.status === filter.key
                                    ? 'default'
                                    : 'outline'
                            }
                            aria-pressed={filters.status === filter.key}
                            onClick={() =>
                                router.get(
                                    notifications.index().url,
                                    filter.key === 'all'
                                        ? {}
                                        : { status: filter.key },
                                    {
                                        preserveState: true,
                                        preserveScroll: true,
                                    },
                                )
                            }
                        >
                            {filter.label}
                            <span
                                className={cn(
                                    'rounded-full px-1.5 text-xs',
                                    filters.status === filter.key
                                        ? 'bg-primary-foreground/20'
                                        : 'bg-muted',
                                )}
                            >
                                {counts[filter.key]}
                            </span>
                        </Button>
                    ))}
                </div>

                <div className="overflow-hidden rounded-sm border shadow-sm">
                    {notificationItems.data.length > 0 ? (
                        <ul className="divide-y">
                            {notificationItems.data.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={notifications.open(item.id).url}
                                        className="flex gap-3 px-4 py-4 transition-colors hover:bg-muted/50"
                                    >
                                        <div className="min-w-0 flex-1 space-y-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p
                                                    className={cn(
                                                        'text-sm',
                                                        item.read_at === null &&
                                                            'font-semibold',
                                                    )}
                                                >
                                                    {item.title}
                                                </p>
                                                <Badge
                                                    variant={kindVariant(
                                                        item.kind,
                                                    )}
                                                >
                                                    {kindLabel(item.kind)}
                                                </Badge>
                                            </div>
                                            {item.message && (
                                                <p className="text-sm text-muted-foreground">
                                                    {item.message}
                                                </p>
                                            )}
                                        </div>
                                        <time
                                            className="shrink-0 text-xs text-muted-foreground"
                                            dateTime={
                                                item.created_at ?? undefined
                                            }
                                        >
                                            {formatDateTime(item.created_at)}
                                        </time>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="flex flex-col items-center gap-2 px-4 py-16 text-center">
                            <Bell className="size-8 text-muted-foreground/50" />
                            <p className="text-sm text-muted-foreground">
                                {filters.status === 'unread'
                                    ? "You're all caught up — no unread notifications."
                                    : filters.status === 'read'
                                      ? 'No read notifications yet.'
                                      : 'No notifications yet.'}
                            </p>
                        </div>
                    )}
                </div>

                <Pagination data={notificationItems} />
            </div>
        </>
    );
}

NotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Inbox', href: notifications.index().url }],
};
