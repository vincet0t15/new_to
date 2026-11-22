import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type StatCardProps = {
    label: string;
    value: number;
    description?: string;
    href?: string;
    icon?: LucideIcon;
    tone?: 'default' | 'warning' | 'success';
};

const valueTone: Record<NonNullable<StatCardProps['tone']>, string> = {
    default: 'text-foreground',
    warning: 'text-amber-600 dark:text-amber-400',
    success: 'text-emerald-600 dark:text-emerald-400',
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
    const className = cn(
        'flex flex-col gap-2 rounded-xl border bg-card p-4 text-card-foreground shadow-sm',
        href &&
            'transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
    );

    const content = (
        <>
            <div className="flex items-center justify-between gap-2">
                <p className="text-sm font-medium text-muted-foreground">
                    {label}
                </p>
                {Icon && (
                    <Icon
                        aria-hidden="true"
                        className="size-4 shrink-0 text-muted-foreground"
                    />
                )}
            </div>
            <p
                className={cn(
                    'text-2xl font-semibold tabular-nums',
                    valueTone[tone],
                )}
            >
                {value}
            </p>
            {description && (
                <p className="text-xs text-muted-foreground">{description}</p>
            )}
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
