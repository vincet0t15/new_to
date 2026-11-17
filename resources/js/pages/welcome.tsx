import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    Check,
    CheckCircle2,
    Clock3,
    FileCheck2,
    MapPin,
    ShieldCheck,
    Workflow,
} from 'lucide-react';
import { dashboard, home, login, register } from '@/routes';

const features = [
    {
        icon: FileCheck2,
        title: 'One clear request',
        description:
            'Keep trip details, destinations, and authorized expenses together in one place.',
    },
    {
        icon: Workflow,
        title: 'A visible approval path',
        description:
            'Know which office reviews the request next and where it stands along the way.',
    },
    {
        icon: ShieldCheck,
        title: 'An accountable record',
        description:
            'Preserve decisions and actions so each travel order is easier to track.',
    },
];

const sampleSteps = [
    { office: 'Department Head', status: 'Approved', complete: true },
    { office: 'Budget Office', status: 'In review', current: true },
    { office: "Mayor's Office", status: 'Up next' },
    { office: 'Human Resources', status: 'Up next' },
];

export default function Welcome() {
    const { auth, name } = usePage().props;
    const primaryHref = auth.user ? dashboard() : login();
    const primaryLabel = auth.user
        ? 'Open your dashboard'
        : 'Sign in to your workspace';

    return (
        <>
            <Head title="Home" />
            <div className="relative isolate min-h-screen overflow-hidden bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-slate-50">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[680px] bg-[radial-gradient(ellipse_at_top_right,rgba(16,185,129,0.16),transparent_58%),radial-gradient(ellipse_at_top_left,rgba(14,116,144,0.1),transparent_52%)]"
                />

                <header className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8">
                    <Link
                        href={home()}
                        className="flex min-w-0 items-center gap-3"
                    >
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-800 text-white shadow-lg shadow-emerald-950/15 dark:bg-emerald-500 dark:text-emerald-950">
                            <FileCheck2 className="size-6" />
                        </span>
                        <span className="grid min-w-0">
                            <span className="max-w-[120px] text-xs leading-tight font-bold tracking-tight sm:max-w-none sm:text-base">
                                {name}
                            </span>
                            <span className="hidden text-[11px] font-medium tracking-[0.16em] text-slate-500 uppercase sm:block dark:text-slate-400">
                                Travel management
                            </span>
                        </span>
                    </Link>

                    <nav
                        aria-label="Main navigation"
                        className="flex shrink-0 items-center gap-2 sm:gap-3"
                    >
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex h-10 items-center gap-2 rounded-full bg-emerald-800 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400"
                            >
                                Dashboard
                                <ArrowRight className="size-4" />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-flex h-10 items-center rounded-full px-3 text-sm font-semibold text-slate-700 transition hover:bg-white/70 hover:text-slate-950 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:text-slate-200 dark:hover:bg-white/10 dark:hover:text-white"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-flex h-10 items-center rounded-full border border-slate-200 bg-white/80 px-4 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-emerald-700 hover:text-emerald-800 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100 dark:hover:border-emerald-400 dark:hover:text-emerald-300"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                <main>
                    <section className="mx-auto grid max-w-7xl items-center gap-12 px-5 pt-10 pb-16 sm:px-8 sm:pt-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:pt-20 lg:pb-24">
                        <div className="max-w-2xl">
                            <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/75 px-3.5 py-2 text-xs font-semibold tracking-wide text-emerald-900 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">
                                <span className="size-2 rounded-full bg-emerald-500" />
                                LOCAL GOVERNMENT TRAVEL PORTAL
                            </div>
                            <h1 className="text-4xl leading-[1.08] font-semibold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl dark:text-white">
                                Official travel,
                                <span className="mt-1 block text-emerald-800 dark:text-emerald-400">
                                    with a clear path forward.
                                </span>
                            </h1>
                            <p className="mt-6 max-w-xl text-base leading-7 text-slate-600 sm:text-lg sm:leading-8 dark:text-slate-300">
                                Prepare travel requests, follow each review, and
                                keep important decisions together from
                                submission to completion.
                            </p>

                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href={primaryHref}
                                    className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-emerald-800 px-6 text-sm font-semibold text-white shadow-lg shadow-emerald-950/15 transition hover:-translate-y-0.5 hover:bg-emerald-900 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400"
                                >
                                    {primaryLabel}
                                    <ArrowRight className="size-4" />
                                </Link>
                                <a
                                    href="#features"
                                    className="inline-flex h-12 items-center justify-center rounded-full border border-slate-200 bg-white/75 px-6 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-900"
                                >
                                    Explore the workflow
                                </a>
                            </div>

                            <div className="mt-9 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-600 dark:text-slate-300">
                                <span className="inline-flex items-center gap-2">
                                    <Check className="size-4 text-emerald-700 dark:text-emerald-400" />
                                    Office-based reviews
                                </span>
                                <span className="inline-flex items-center gap-2">
                                    <Check className="size-4 text-emerald-700 dark:text-emerald-400" />
                                    Clear status tracking
                                </span>
                            </div>
                        </div>

                        <div className="relative mx-auto w-full max-w-xl">
                            <div
                                aria-hidden="true"
                                className="absolute -inset-5 rounded-[2.5rem] bg-gradient-to-br from-emerald-200/60 via-cyan-100/40 to-amber-100/60 blur-2xl dark:from-emerald-900/30 dark:via-cyan-950/20 dark:to-amber-950/20"
                            />
                            <div className="relative overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-2xl shadow-slate-900/10 backdrop-blur sm:p-7 dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-black/30">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-xs font-semibold tracking-[0.16em] text-emerald-800 uppercase dark:text-emerald-300">
                                            Sample approval path
                                        </p>
                                        <h2 className="mt-2 text-xl font-semibold tracking-tight">
                                            Local Travel Order
                                        </h2>
                                    </div>
                                    <span className="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        4 review steps
                                    </span>
                                </div>

                                <div className="mt-6 flex items-center gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/70">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-800 shadow-sm dark:bg-slate-700 dark:text-emerald-300">
                                        <MapPin className="size-5" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-xs font-medium text-slate-500 dark:text-slate-400">
                                            Destination
                                        </p>
                                        <p className="truncate text-sm font-semibold">
                                            Filed and routed by office
                                        </p>
                                    </div>
                                    <Building2 className="size-5 shrink-0 text-slate-400" />
                                </div>

                                <ol className="mt-6">
                                    {sampleSteps.map((step, index) => (
                                        <li
                                            key={step.office}
                                            className="flex gap-4"
                                        >
                                            <div className="flex flex-col items-center">
                                                <span
                                                    className={`flex size-8 shrink-0 items-center justify-center rounded-full ${
                                                        step.complete
                                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                            : step.current
                                                              ? 'bg-amber-100 text-amber-800 ring-4 ring-amber-50 dark:bg-amber-950 dark:text-amber-300 dark:ring-amber-950/50'
                                                              : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500'
                                                    }`}
                                                >
                                                    {step.complete ? (
                                                        <CheckCircle2 className="size-4" />
                                                    ) : (
                                                        <span className="text-xs font-bold">
                                                            {index + 1}
                                                        </span>
                                                    )}
                                                </span>
                                                {index <
                                                    sampleSteps.length - 1 && (
                                                    <span className="my-1 h-6 w-px bg-slate-200 dark:bg-slate-700" />
                                                )}
                                            </div>
                                            <div className="flex min-h-14 flex-1 items-start justify-between gap-3 pt-1">
                                                <div>
                                                    <p className="text-sm font-semibold">
                                                        {step.office}
                                                    </p>
                                                    <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                                        {step.complete
                                                            ? 'Review completed'
                                                            : step.current
                                                              ? 'Assigned reviewers notified'
                                                              : 'Waiting for previous review'}
                                                    </p>
                                                </div>
                                                <span
                                                    className={`mt-0.5 shrink-0 text-xs font-semibold ${
                                                        step.complete
                                                            ? 'text-emerald-700 dark:text-emerald-400'
                                                            : step.current
                                                              ? 'text-amber-700 dark:text-amber-300'
                                                              : 'text-slate-400 dark:text-slate-500'
                                                    }`}
                                                >
                                                    {step.status}
                                                </span>
                                            </div>
                                        </li>
                                    ))}
                                </ol>

                                <div className="mt-5 flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                                    <span className="flex size-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        <Clock3 className="size-4" />
                                    </span>
                                    <p className="text-xs leading-5 text-slate-600 dark:text-slate-300">
                                        <span className="font-semibold text-slate-900 dark:text-white">
                                            Every step stays visible.
                                        </span>{' '}
                                        Track reviews without losing the
                                        decision history.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        id="features"
                        className="border-y border-slate-200/80 bg-white/70 py-16 sm:py-20 dark:border-slate-800 dark:bg-slate-900/40"
                    >
                        <div className="mx-auto max-w-7xl px-5 sm:px-8">
                            <div className="max-w-2xl">
                                <p className="text-xs font-semibold tracking-[0.16em] text-emerald-800 uppercase dark:text-emerald-300">
                                    Designed for public service
                                </p>
                                <h2 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                                    Everything important, in one process.
                                </h2>
                                <p className="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                                    A practical workspace for preparing,
                                    reviewing, and keeping track of official
                                    travel.
                                </p>
                            </div>

                            <div className="mt-9 grid gap-4 md:grid-cols-3">
                                {features.map((feature) => {
                                    const Icon = feature.icon;

                                    return (
                                        <article
                                            key={feature.title}
                                            className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                                        >
                                            <span className="flex size-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                <Icon className="size-5" />
                                            </span>
                                            <h3 className="mt-5 text-base font-semibold">
                                                {feature.title}
                                            </h3>
                                            <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                                {feature.description}
                                            </p>
                                        </article>
                                    );
                                })}
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 dark:text-slate-400">
                    <span className="font-semibold text-slate-700 dark:text-slate-200">
                        {name}
                    </span>
                    <span>
                        A clearer, more accountable way to manage official
                        travel.
                    </span>
                </footer>
            </div>
        </>
    );
}
