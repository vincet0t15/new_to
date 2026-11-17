import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    BadgeCheck,
    Building2,
    FileCheck2,
    Workflow,
} from 'lucide-react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative min-h-svh overflow-hidden bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-slate-50">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(16,185,129,0.12),transparent_45%),radial-gradient(ellipse_at_bottom_left,rgba(14,116,144,0.08),transparent_42%)]"
            />
            <main className="relative mx-auto grid min-h-svh max-w-7xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(400px,0.95fr)] lg:gap-14 lg:px-8">
                <aside className="relative hidden min-h-[680px] flex-col justify-between overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-950 via-emerald-900 to-slate-950 p-10 text-white shadow-2xl lg:flex xl:p-14">
                    <div
                        aria-hidden="true"
                        className="absolute -top-32 -right-32 size-96 rounded-full border border-white/10"
                    />
                    <div
                        aria-hidden="true"
                        className="absolute right-8 bottom-16 size-64 rounded-full border border-white/10"
                    />
                    <Link
                        href={home()}
                        className="relative flex items-center gap-3"
                    >
                        <span className="flex size-11 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/15">
                            <FileCheck2 className="size-6" />
                        </span>
                        <span className="grid">
                            <span className="text-sm font-bold">{name}</span>
                            <span className="text-[11px] tracking-[0.16em] text-emerald-100/70 uppercase">
                                Local government portal
                            </span>
                        </span>
                    </Link>

                    <div className="relative py-10">
                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-semibold tracking-[0.15em] text-emerald-100 uppercase">
                            <Building2 className="size-3.5" />
                            Travel order workspace
                        </span>
                        <h2 className="mt-6 max-w-xl text-4xl leading-tight font-semibold tracking-tight xl:text-5xl">
                            Official travel, with a clear path from request to
                            approval.
                        </h2>
                        <p className="mt-5 max-w-lg text-sm leading-7 text-emerald-50/75">
                            Prepare a request, follow its office reviews, and
                            keep each decision connected to the travel order.
                        </p>

                        <div className="mt-9 space-y-3">
                            <div className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span className="flex size-9 items-center justify-center rounded-xl bg-white/10 text-emerald-100">
                                    <FileCheck2 className="size-4" />
                                </span>
                                <span className="text-sm font-medium">
                                    Complete travel details in one request
                                </span>
                            </div>
                            <div className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span className="flex size-9 items-center justify-center rounded-xl bg-white/10 text-emerald-100">
                                    <Workflow className="size-4" />
                                </span>
                                <span className="text-sm font-medium">
                                    Follow the assigned review workflow
                                </span>
                            </div>
                            <div className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span className="flex size-9 items-center justify-center rounded-xl bg-white/10 text-emerald-100">
                                    <BadgeCheck className="size-4" />
                                </span>
                                <span className="text-sm font-medium">
                                    Keep actions and decisions traceable
                                </span>
                            </div>
                        </div>
                    </div>

                    <p className="relative text-xs text-emerald-100/65">
                        A more transparent way to manage official travel.
                    </p>
                </aside>

                <section className="mx-auto w-full max-w-md">
                    <Link
                        href={home()}
                        className="mb-7 flex items-center justify-center gap-3 lg:hidden"
                    >
                        <span className="flex size-10 items-center justify-center rounded-2xl bg-emerald-800 text-white shadow-md dark:bg-emerald-500 dark:text-emerald-950">
                            <FileCheck2 className="size-5" />
                        </span>
                        <span className="text-sm font-bold">{name}</span>
                    </Link>

                    <div className="rounded-[1.75rem] border border-slate-200/80 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-9 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                        <div className="mb-7">
                            <p className="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                <Building2 className="size-3.5" />
                                Secure portal access
                            </p>
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                {title}
                            </h1>
                            <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                {description}
                            </p>
                        </div>

                        {children}
                    </div>

                    <div className="mt-5 flex items-center justify-center gap-2 text-center text-xs text-slate-500 dark:text-slate-400">
                        <BadgeCheck className="size-4 text-emerald-700 dark:text-emerald-400" />
                        Your account and workflow access are protected.
                    </div>
                    <Link
                        href={home()}
                        className="mt-6 inline-flex w-full items-center justify-center gap-2 text-sm font-medium text-slate-600 transition hover:text-emerald-800 dark:text-slate-400 dark:hover:text-emerald-300"
                    >
                        <ArrowLeft className="size-4" />
                        Back to home
                    </Link>
                </section>
            </main>
        </div>
    );
}
