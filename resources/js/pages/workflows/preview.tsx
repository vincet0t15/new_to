import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

    return (
        <>
            <Head title="Route Preview" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Route Preview"
                    description="See the chain an office resolves to, with its current designees."
                />

                <div className="flex items-center gap-2">
                    <Select value={officeId} onValueChange={handleSelect}>
                        <SelectTrigger className="w-[280px]">
                            <SelectValue placeholder="Select an office" />
                        </SelectTrigger>
                        <SelectContent>
                            {offices.map((office) => (
                                <SelectItem
                                    key={office.id}
                                    value={String(office.id)}
                                >
                                    {office.office_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {preview && (
                    <div className="space-y-4">
                        <div className="rounded-xl border p-4">
                            <div className="flex items-center gap-2">
                                <h3 className="text-lg font-semibold">
                                    {preview.office.office_name}
                                </h3>
                                {preview.workflow && (
                                    <Badge
                                        variant={
                                            preview.uses_override
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {preview.workflow.name}
                                        {preview.workflow.is_default
                                            ? ' (default)'
                                            : ''}
                                    </Badge>
                                )}
                                {preview.is_ready ? (
                                    <CheckCircle2 className="size-5 text-green-600" />
                                ) : (
                                    <AlertTriangle className="size-5 text-amber-600" />
                                )}
                            </div>
                            {preview.fallback_reason && (
                                <p className="mt-2 text-sm text-amber-600">
                                    {preview.fallback_reason}
                                </p>
                            )}
                            {preview.warning && (
                                <p className="mt-2 text-sm text-red-600">
                                    {preview.warning}
                                </p>
                            )}
                        </div>

                        <div className="space-y-3">
                            {preview.steps.map((step, index) => (
                                <div
                                    key={step.id}
                                    className="rounded-xl border p-4"
                                >
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-muted-foreground">
                                            {index + 1}.
                                        </span>
                                        <span className="font-medium">
                                            {step.label}
                                        </span>
                                        <Badge variant="outline">
                                            {step.step_type.is_signatory
                                                ? 'Signatory'
                                                : 'Receipt'}
                                        </Badge>
                                        {step.uses_filing_office && (
                                            <Badge variant="secondary">
                                                Filing office
                                            </Badge>
                                        )}
                                        {step.is_ready ? (
                                            <CheckCircle2 className="size-4 text-green-600" />
                                        ) : (
                                            <AlertTriangle className="size-4 text-amber-600" />
                                        )}
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Target office: {step.office.office_name}
                                    </p>
                                    {step.warning && (
                                        <p className="mt-1 text-sm text-amber-600">
                                            {step.warning}
                                        </p>
                                    )}
                                    {step.designees.length > 0 && (
                                        <ul className="mt-2 space-y-1 text-sm">
                                            {step.designees.map((designee) => (
                                                <li
                                                    key={designee.id}
                                                    className="flex items-center gap-2"
                                                >
                                                    <span>{designee.name}</span>
                                                    {designee.is_deputy && (
                                                        <Badge variant="outline">
                                                            Deputy
                                                        </Badge>
                                                    )}
                                                    {!designee.has_account && (
                                                        <span className="text-xs text-red-600">
                                                            (no account)
                                                        </span>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
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
