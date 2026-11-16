import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Combobox } from '@/components/ui/combobox';
import { Spinner } from '@/components/ui/spinner';
import offices from '@/routes/offices';
import type { OfficeCreateRequest } from '@/types/office';
import type { WorkflowOption } from '@/types/workflow';
import type { FormEvent } from 'react';

type CreateOfficeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    workflows: WorkflowOption[];
};

export function CreateOfficeDialog({
    isOpen,
    onClose,
    workflows,
}: CreateOfficeDialogProps) {
    const { data, setData, post, reset, errors, processing } =
        useForm<OfficeCreateRequest>({
            office_name: '',
            office_code: '',
            workflow_id: '',
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(offices.store().url, {
            onSuccess: () => {
                onClose();
                reset();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Create Office</DialogTitle>
                        <DialogDescription>
                            Add a new office by providing the required details.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="office_name">Office Name *</Label>
                            <Input
                                id="office_name"
                                name="office_name"
                                placeholder="e.g., Main Office"
                                value={data.office_name}
                                onChange={(event) =>
                                    setData('office_name', event.target.value)
                                }
                                required
                                autoFocus
                            />
                            <InputError message={errors.office_name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="office_code">Office Code</Label>
                            <Input
                                id="office_code"
                                name="office_code"
                                placeholder="e.g., MO-01"
                                value={data.office_code}
                                onChange={(event) =>
                                    setData('office_code', event.target.value)
                                }
                            />
                            <p className="text-xs text-muted-foreground">
                                Optional. A short code used on reports and
                                records.
                            </p>
                            <InputError message={errors.office_code} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="workflow_id">
                                Approval Workflow
                            </Label>
                            <Combobox
                                id="workflow_id"
                                value={
                                    data.workflow_id === ''
                                        ? 'default'
                                        : String(data.workflow_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'workflow_id',
                                        value === 'default'
                                            ? ''
                                            : Number(value),
                                    )
                                }
                                options={[
                                    {
                                        value: 'default',
                                        label: 'Default workflow (fallback)',
                                    },
                                    ...workflows.map((workflow) => ({
                                        value: String(workflow.id),
                                        label: workflow.is_active
                                            ? workflow.name
                                            : `${workflow.name} (inactive)`,
                                    })),
                                ]}
                                placeholder="Default workflow"
                                searchPlaceholder="Search workflow..."
                                emptyMessage="No workflows found."
                            />
                            <p className="text-xs text-muted-foreground">
                                Leave blank to follow the default chain.
                            </p>
                            <InputError message={errors.workflow_id} />
                        </div>
                    </div>

                    <DialogFooter className="mt-4">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Create Office
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
