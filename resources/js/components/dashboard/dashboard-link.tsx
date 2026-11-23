import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * The quiet text action used in dashboard section and panel headers.
 */
export function DashboardLink({
    href,
    children,
}: {
    href: string;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            className="inline-flex items-center gap-1 text-xs font-medium text-emerald-800 underline-offset-4 hover:underline dark:text-emerald-400"
        >
            {children}
            <ArrowRight aria-hidden="true" className="size-3.5" />
        </Link>
    );
}
