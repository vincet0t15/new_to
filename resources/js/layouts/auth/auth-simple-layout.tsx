import { Link, usePage } from '@inertiajs/react';
import { Building2, FileCheck2 } from 'lucide-react';
import { home } from '@/routes';
import { cn } from '@/lib/utils';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
    wide = false,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative min-h-svh overflow-hidden bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-slate-50">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(16,185,129,0.12),transparent_45%),radial-gradient(ellipse_at_bottom_left,rgba(14,116,144,0.08),transparent_42%)]"
            />
            <main className="relative mx-auto flex min-h-svh w-full items-center justify-center px-4 py-4 sm:px-6">
                <section
                    className={cn('w-full', wide ? 'max-w-2xl' : 'max-w-md')}
                >
                    <Link
                        href={home()}
                        className="mb-4 flex items-center justify-center gap-3"
                    >
                        <span className="flex size-10 items-center justify-center rounded-2xl bg-emerald-800 text-white shadow-md dark:bg-emerald-500 dark:text-emerald-950">
                            <FileCheck2 className="size-5" />
                        </span>
                        <span className="text-sm font-bold">{name}</span>
                    </Link>

                    <div
                        className={cn(
                            'rounded-[1.75rem] border border-slate-200/80 bg-white p-6 shadow-xl shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20',
                            wide ? 'sm:p-6' : 'sm:p-8',
                        )}
                    >
                        <div className="mb-4">
                            <p className="mb-2 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                <Building2 className="size-3.5" />
                                Secure portal access
                            </p>
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                {title}
                            </h1>
                            <p className="mt-1.5 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                {description}
                            </p>
                        </div>

                        {children}
                    </div>
                </section>
            </main>
        </div>
    );
}
