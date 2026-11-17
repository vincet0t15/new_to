import { Check, Circle, Clock3, RotateCcw, SkipForward, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import type {
    TravelOrderRouteStep,
    TravelOrderRouteStepStatus,
} from '@/types/travel-order';

const statusLabels: Record<TravelOrderRouteStepStatus, string> = {
    planned: 'Planned',
    upcoming: 'Upcoming',
    current: 'Waiting here',
    cleared: 'Cleared',
    skipped: 'Skipped',
    returned: 'Returned here',
    cancelled: 'Cancelled',
};

const statusStyles: Record<TravelOrderRouteStepStatus, string> = {
    planned:
        'border-slate-300 bg-slate-50 text-slate-600 dark:bg-slate-900 dark:text-slate-300',
    upcoming:
        'border-slate-300 bg-white text-slate-600 dark:bg-slate-950 dark:text-slate-300',
    current:
        'border-blue-500 bg-blue-50 text-blue-700 ring-2 ring-blue-500/20 dark:bg-blue-950 dark:text-blue-200',
    cleared:
        'border-emerald-400 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
    skipped:
        'border-slate-300 bg-slate-100 text-slate-500 dark:bg-slate-900 dark:text-slate-400',
    returned:
        'border-amber-400 bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
    cancelled:
        'border-red-300 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-200',
};

function StepIcon({ status }: { status: TravelOrderRouteStepStatus }) {
    if (status === 'cleared') {
        return <Check className="size-4" />;
    }

    if (status === 'skipped') {
        return <SkipForward className="size-4" />;
    }

    if (status === 'returned') {
        return <RotateCcw className="size-4" />;
    }

    if (status === 'cancelled') {
        return <X className="size-4" />;
    }

    if (status === 'current') {
        return <Clock3 className="size-4" />;
    }

    return <Circle className="size-4" />;
}

export function RouteProgress({ steps }: { steps: TravelOrderRouteStep[] }) {
    if (steps.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No approval route is available yet.
            </p>
        );
    }

    return (
        <ol className="space-y-3">
            {steps.map((step) => (
                <li
                    key={step.index}
                    className={cn(
                        'flex items-start gap-3 rounded-lg border p-3',
                        statusStyles[step.status],
                    )}
                >
                    <span className="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full border bg-background/60">
                        <StepIcon status={step.status} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <p className="text-sm font-medium">{step.label}</p>
                            <span className="text-xs tracking-wide uppercase opacity-70">
                                {statusLabels[step.status]}
                            </span>
                            {!step.is_signatory && (
                                <span className="text-xs text-muted-foreground">
                                    Receipt only
                                </span>
                            )}
                        </div>
                        <p className="text-xs opacity-80">{step.office}</p>
                        {step.holders.length > 0 && (
                            <p className="mt-1 text-xs">
                                With{' '}
                                {step.holders
                                    .map((holder) => holder.name)
                                    .join(', ')}
                            </p>
                        )}
                    </div>
                </li>
            ))}
        </ol>
    );
}
