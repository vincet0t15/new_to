import type { TravelOrderTimelineEntry } from '@/types/travel-order';

const eventStyles: Record<string, string> = {
    submitted: 'text-blue-600 dark:text-blue-300',
    routed: 'text-slate-500 dark:text-slate-400',
    received: 'text-slate-600 dark:text-slate-300',
    approved: 'text-emerald-600 dark:text-emerald-300',
    acknowledged: 'text-emerald-600 dark:text-emerald-300',
    forwarded: 'text-indigo-600 dark:text-indigo-300',
    returned: 'text-amber-600 dark:text-amber-300',
    resubmitted: 'text-blue-600 dark:text-blue-300',
    step_skipped: 'text-slate-500 dark:text-slate-400',
    filer_withheld: 'text-slate-500 dark:text-slate-400',
    completed: 'text-emerald-600 dark:text-emerald-300',
};

function formatDate(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export function ActionTimeline({
    actions,
}: {
    actions: TravelOrderTimelineEntry[];
}) {
    if (actions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Nothing has happened yet.
            </p>
        );
    }

    return (
        <ol className="space-y-4">
            {actions.map((action) => (
                <li key={action.id} className="relative border-l pl-4">
                    <span className="absolute top-1.5 -left-[5px] size-2 rounded-full bg-current" />
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <p
                            className={`text-sm font-medium ${eventStyles[action.event] ?? ''}`}
                        >
                            {action.event_label}
                            {action.step_label ? ` — ${action.step_label}` : ''}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {formatDate(action.created_at)}
                        </p>
                    </div>
                    {action.actor && (
                        <p className="text-xs text-muted-foreground">
                            by {action.actor}
                        </p>
                    )}
                    {action.remarks && (
                        <p className="mt-1 text-sm">{action.remarks}</p>
                    )}
                </li>
            ))}
        </ol>
    );
}
