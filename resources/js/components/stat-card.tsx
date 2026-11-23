import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type StatCardProps = {
    label: string;
    value: number;
    description?: string;
    href?: string;
    icon?: LucideIcon;
    tone?: 'default' | 'info' | 'warning' | 'success' | 'danger';
};

const toneStyles: Record<
    NonNullable<StatCardProps['tone']>,
    { icon: string; dot: string }
> = {
    default: {
        icon: 'border-border/70 bg-muted/50 text-muted-foreground',
        dot: 'bg-slate-400 dark:bg-slate-500',
    },
    info: {
        icon: 'border-sky-600/10 bg-sky-50 text-sky-700 dark:border-sky-400/10 dark:bg-sky-950/50 dark:text-sky-300',
        dot: 'bg-sky-500',
    },
    warning: {
        icon: 'border-amber-600/10 bg-amber-50 text-amber-700 dark:border-amber-400/10 dark:bg-amber-950/50 dark:text-amber-300',
        dot: 'bg-amber-500',
    },
    success: {
        icon: 'border-emerald-600/10 bg-emerald-50 text-emerald-700 dark:border-emerald-400/10 dark:bg-emerald-950/50 dark:text-emerald-300',
        dot: 'bg-emerald-500',
    },
    danger: {
        icon: 'border-rose-600/10 bg-rose-50 text-rose-700 dark:border-rose-400/10 dark:bg-rose-950/50 dark:text-rose-300',
        dot: 'bg-rose-500',
    },
};

/**
 * One dashboard figure. With an href it becomes a shortcut to the matching
 * queue; without one it is a plain read-only summary.
 */
export function StatCard({
    label,
    value,
    description,
    href,
    icon: Icon,
    tone = 'default',
}: StatCardProps) {
    // A zero count is not an alert: keep the tile quiet until there is
    // something to act on.
    const activeTone = value === 0 ? 'default' : tone;
    const styles = toneStyles[activeTone];

    const className = cn(
        'group flex min-h-[7.75rem] flex-col gap-3 rounded-xl border border-border/80 bg-card p-5 text-card-foreground shadow-xs',
        href &&
            'transition-colors hover:border-foreground/15 hover:bg-card focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
    );

    const content = (
        <>
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-2 pt-1">
                    <span
                        aria-hidden="true"
                        className={cn('size-1.5 rounded-full', styles.dot)}
                    />
                    <p className="text-[13px] font-medium text-muted-foreground">
                        {label}
                    </p>
                </div>
                {Icon && (
                    <span
                        className={cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-lg border',
                            styles.icon,
                        )}
                    >
                        <Icon aria-hidden="true" className="size-4" />
                    </span>
                )}
            </div>

            <p className="text-3xl font-semibold tracking-tight text-foreground tabular-nums">
                {value}
            </p>

            <div className="mt-auto flex items-end justify-between gap-2">
                {description && (
                    <p className="text-xs leading-5 text-muted-foreground">
                        {description}
                    </p>
                )}
                {href && (
                    <ArrowUpRight
                        aria-hidden="true"
                        className="size-4 shrink-0 text-muted-foreground/50 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-foreground"
                    />
                )}
            </div>
        </>
    );

    return href ? (
        <Link href={href} className={className}>
            {content}
        </Link>
    ) : (
        <div className={className}>{content}</div>
    );
}
