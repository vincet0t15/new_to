import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDown,
    Building2,
    CheckCircle2,
    GitBranch,
    Settings,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import workflowsRoutes from '@/routes/workflows';
import type { Office } from '@/types/office';
import type { WorkflowPreview } from '@/types/workflow';

type Props = {
    offices: Pick<Office, 'id' | 'office_name' | 'workflow_id'>[];
    selectedOfficeId: number | null;
    preview: WorkflowPreview | null;
};

export default function WorkflowPreviewPage({
    offices,
    selectedOfficeId,
    preview,
}: Props) {
    const [officeId, setOfficeId] = useState<string>(
        selectedOfficeId ? String(selectedOfficeId) : '',
    );

    const handleSelect = (value: string) => {
        setOfficeId(value);
        router.get(
            workflowsRoutes.preview().url,
            { office_id: value },
            { preserveState: true, preserveScroll: true },
        );
    };

    const selectedOffice = offices.find(
        (office) => String(office.id) === officeId,
    );

    const readySteps =
        preview?.steps.filter((step) => step.is_ready).length ?? 0;
    const totalSteps = preview?.steps.length ?? 0;
    const notReadySteps = preview?.steps.filter((step) => !step.is_ready) ?? [];
    const totalDesignees =
        preview?.steps.reduce(
            (count, step) => count + step.designees.length,
            0,
        ) ?? 0;

    return (
        <>
            <Head title="Route Preview" />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-6 rounded-xl p-4">
                <Heading
                    title="Route Preview"
                    description="See the chain an office resolves to, with its current designees and readiness."
                />

                {/* Office selector */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Building2 className="size-4" />
                            Select an office
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Combobox
                                ariaLabel="Office"
                                value={officeId}
                                onValueChange={handleSelect}
                                options={offices.map((office) => ({
                                    value: String(office.id),
                                    label: office.office_name,
                                }))}
                                placeholder="Choose an office to preview"
                                searchPlaceholder="Search office..."
                                emptyMessage="No offices found."
                                className="w-full sm:w-[360px]"
                            />
                            {selectedOffice && (
                                <p className="text-sm text-muted-foreground">
                                    Previewing the resolved route for{' '}
                                    <span className="font-medium text-foreground">
                                        {selectedOffice.office_name}
                                    </span>
                                    .
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Empty state */}
                {!preview && (
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center justify-center gap-3 py-12 text-center">
                            <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                                <GitBranch className="size-6 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">
                                    No office selected
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Pick an office above to see the approval
                                    chain it resolves to, including the
                                    designees who answer each step.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {preview && (
                    <>
                        {/* Summary card */}
                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-2">
                                            {preview.office.office_name}
                                            {preview.is_ready ? (
                                                <Badge
                                                    variant="default"
                                                    className="bg-green-600 text-white hover:bg-green-600"
                                                >
                                                    <CheckCircle2 className="mr-1 size-3" />
                                                    Ready
                                                </Badge>
                                            ) : (
                                                <Badge variant="destructive">
                                                    <AlertTriangle className="mr-1 size-3" />
                                                    Not ready
                                                </Badge>
                                            )}
                                        </CardTitle>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {preview.workflow ? (
                                                <>
                                                    Workflow:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {preview.workflow.name}
                                                    </span>
                                                    {preview.workflow
                                                        .is_default && (
                                                        <span className="ml-1 text-xs">
                                                            (default)
                                                        </span>
                                                    )}
                                                    {preview.uses_override && (
                                                        <Badge
                                                            variant="outline"
                                                            className="ml-2"
                                                        >
                                                            Office override
                                                        </Badge>
                                                    )}
                                                </>
                                            ) : (
                                                'No workflow assigned'
                                            )}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2 text-sm">
                                        <div className="rounded-lg border bg-muted/40 px-3 py-1.5">
                                            <span className="text-muted-foreground">
                                                Steps:
                                            </span>{' '}
                                            <span className="font-semibold">
                                                {readySteps}/{totalSteps}
                                            </span>
                                        </div>
                                        <div className="rounded-lg border bg-muted/40 px-3 py-1.5">
                                            <span className="text-muted-foreground">
                                                Designees:
                                            </span>{' '}
                                            <span className="font-semibold">
                                                {totalDesignees}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>

                            {/* Alerts */}
                            {(preview.fallback_reason || preview.warning) && (
                                <CardContent className="pt-0">
                                    <div className="space-y-2">
                                        {preview.fallback_reason && (
                                            <Alert>
                                                <AlertTriangle className="size-4" />
                                                <AlertTitle>
                                                    Fallback notice
                                                </AlertTitle>
                                                <AlertDescription>
                                                    {preview.fallback_reason}
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                        {preview.warning && (
                                            <Alert variant="destructive">
                                                <AlertTriangle className="size-4" />
                                                <AlertTitle>
                                                    Configuration issue
                                                </AlertTitle>
                                                <AlertDescription>
                                                    {preview.warning}
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                    </div>
                                </CardContent>
                            )}
                        </Card>

                        {/* Issues panel */}
                        {notReadySteps.length > 0 && (
                            <Alert variant="destructive">
                                <AlertTriangle className="size-4" />
                                <AlertTitle>
                                    {notReadySteps.length} step
                                    {notReadySteps.length === 1 ? '' : 's'} not
                                    ready
                                </AlertTitle>
                                <AlertDescription>
                                    <ul className="mt-1 list-disc pl-4">
                                        {notReadySteps.map((step) => (
                                            <li key={step.id}>
                                                {step.label} —{' '}
                                                {step.warning ??
                                                    'no designee with a login account'}
                                            </li>
                                        ))}
                                    </ul>
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={
                                                    workflowsRoutes.index().url
                                                }
                                            >
                                                <Settings className="mr-1 size-3" />
                                                Manage workflows
                                            </Link>
                                        </Button>
                                    </div>
                                </AlertDescription>
                            </Alert>
                        )}

                        {/* Steps stepper */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <GitBranch className="size-4" />
                                    Approval chain
                                    <Badge variant="secondary">
                                        {totalSteps} step
                                        {totalSteps === 1 ? '' : 's'}
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {preview.steps.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        This workflow has no active steps.
                                    </p>
                                ) : (
                                    <ol className="relative space-y-0">
                                        {preview.steps.map((step, index) => {
                                            const isLast =
                                                index ===
                                                preview.steps.length - 1;
                                            return (
                                                <li
                                                    key={step.id}
                                                    className="relative pb-6 last:pb-0"
                                                >
                                                    {/* Connector line */}
                                                    {!isLast && (
                                                        <span
                                                            aria-hidden="true"
                                                            className="absolute top-8 left-[15px] h-full w-0.5 bg-border"
                                                        />
                                                    )}
                                                    <div className="flex gap-4">
                                                        {/* Step number circle */}
                                                        <div
                                                            className={`relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold ${
                                                                step.is_ready
                                                                    ? 'border-green-600 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                                                                    : 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                                            }`}
                                                        >
                                                            {step.is_ready ? (
                                                                <CheckCircle2 className="size-4" />
                                                            ) : (
                                                                index + 1
                                                            )}
                                                        </div>

                                                        {/* Step content */}
                                                        <div className="min-w-0 flex-1 rounded-lg border bg-card p-4 shadow-sm">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <span className="font-medium">
                                                                    {step.label}
                                                                </span>
                                                                <Badge variant="outline">
                                                                    {step
                                                                        .step_type
                                                                        .is_signatory
                                                                        ? 'Signatory'
                                                                        : 'Receipt'}
                                                                </Badge>
                                                                {step.uses_filing_office && (
                                                                    <Badge variant="secondary">
                                                                        Filing
                                                                        office
                                                                    </Badge>
                                                                )}
                                                                {step.auto_skip_creator && (
                                                                    <Badge variant="outline">
                                                                        Auto-skip
                                                                        creator
                                                                    </Badge>
                                                                )}
                                                                {step.is_ready ? (
                                                                    <CheckCircle2 className="size-4 text-green-600" />
                                                                ) : (
                                                                    <AlertTriangle className="size-4 text-amber-600" />
                                                                )}
                                                            </div>

                                                            <div className="mt-2 flex items-center gap-1.5 text-sm text-muted-foreground">
                                                                <Building2 className="size-3.5 shrink-0" />
                                                                <span>
                                                                    Target
                                                                    office:{' '}
                                                                    <span className="text-foreground">
                                                                        {
                                                                            step
                                                                                .office
                                                                                .office_name
                                                                        }
                                                                    </span>
                                                                </span>
                                                            </div>

                                                            {step.warning && (
                                                                <p className="mt-2 text-sm text-amber-700 dark:text-amber-300">
                                                                    {
                                                                        step.warning
                                                                    }
                                                                </p>
                                                            )}

                                                            {step.designees
                                                                .length > 0 && (
                                                                <div className="mt-3">
                                                                    <p className="mb-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                                        Designees
                                                                    </p>
                                                                    <ul className="space-y-1.5">
                                                                        {step.designees.map(
                                                                            (
                                                                                designee,
                                                                            ) => (
                                                                                <li
                                                                                    key={
                                                                                        designee.id
                                                                                    }
                                                                                    className="flex items-center gap-2 text-sm"
                                                                                >
                                                                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                                                                        {designee.name
                                                                                            .charAt(
                                                                                                0,
                                                                                            )
                                                                                            .toUpperCase()}
                                                                                    </span>
                                                                                    <span>
                                                                                        {
                                                                                            designee.name
                                                                                        }
                                                                                    </span>
                                                                                    {designee.is_deputy && (
                                                                                        <Badge
                                                                                            variant="outline"
                                                                                            className="text-xs"
                                                                                        >
                                                                                            Deputy
                                                                                        </Badge>
                                                                                    )}
                                                                                    {!designee.has_account && (
                                                                                        <span className="text-xs text-red-600">
                                                                                            (no
                                                                                            account)
                                                                                        </span>
                                                                                    )}
                                                                                </li>
                                                                            ),
                                                                        )}
                                                                    </ul>
                                                                </div>
                                                            )}

                                                            {step.designees
                                                                .length ===
                                                                0 && (
                                                                <p className="mt-2 text-xs text-muted-foreground italic">
                                                                    No designees
                                                                    configured
                                                                    for this
                                                                    step.
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {!isLast && (
                                                        <div className="ml-[14px] flex h-6 items-center">
                                                            <ArrowDown className="size-4 text-muted-foreground/60" />
                                                        </div>
                                                    )}
                                                </li>
                                            );
                                        })}
                                    </ol>
                                )}
                            </CardContent>
                        </Card>

                        {/* Quick links */}
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Quick actions
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={workflowsRoutes.index().url}
                                        >
                                            <GitBranch className="mr-1 size-3" />
                                            Manage workflows
                                        </Link>
                                    </Button>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href="/admin/workflow-step-types">
                                            <Settings className="mr-1 size-3" />
                                            Step types
                                        </Link>
                                    </Button>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href="/admin/designations">
                                            <Users className="mr-1 size-3" />
                                            Designations
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}

WorkflowPreviewPage.layout = {
    breadcrumbs: [
        { title: 'Route Preview', href: workflowsRoutes.preview().url },
    ],
};
