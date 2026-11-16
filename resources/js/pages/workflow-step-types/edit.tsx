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
import { Spinner } from '@/components/ui/spinner';
import workflowStepTypes from '@/routes/workflow-step-types';
import type {
    WorkflowStepType,
    WorkflowStepTypeRequest,
} from '@/types/workflow';
import type { FormEvent } from 'react';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    stepType: WorkflowStepType;
};

export function EditStepTypeDialog({ isOpen, onClose, stepType }: Props) {
    const { data, setData, put, errors, processing } =
        useForm<WorkflowStepTypeRequest>({
            name: stepType.name,
            slug: stepType.slug,
            signature_label: stepType.signature_label ?? '',
            signature_title: stepType.signature_title ?? '',
            is_signatory: stepType.is_signatory,
            sort_order: stepType.sort_order,
            is_active: stepType.is_active,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        put(workflowStepTypes.update(stepType).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Step Type</DialogTitle>
                        <DialogDescription>
                            Update the reusable step definition.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                required
                                autoFocus
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="slug">Slug *</Label>
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(e) =>
                                    setData('slug', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.slug} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="signature_label">
                                Signature Label
                            </Label>
                            <Input
                                id="signature_label"
                                value={data.signature_label}
                                onChange={(e) =>
                                    setData('signature_label', e.target.value)
                                }
                            />
                            <InputError message={errors.signature_label} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="signature_title">
                                Signature Title
                            </Label>
                            <Input
                                id="signature_title"
                                value={data.signature_title}
                                onChange={(e) =>
                                    setData('signature_title', e.target.value)
                                }
                            />
                            <InputError message={errors.signature_title} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sort_order">Sort Order *</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                min={0}
                                value={data.sort_order}
                                onChange={(e) =>
                                    setData(
                                        'sort_order',
                                        Number(e.target.value),
                                    )
                                }
                                required
                            />
                            <InputError message={errors.sort_order} />
                        </div>
                        <div className="flex items-center gap-2">
                            <input
                                id="is_signatory"
                                type="checkbox"
                                checked={data.is_signatory}
                                onChange={(e) =>
                                    setData('is_signatory', e.target.checked)
                                }
                            />
                            <Label htmlFor="is_signatory">
                                Signatory (not just receipt)
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <input
                                id="is_active"
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) =>
                                    setData('is_active', e.target.checked)
                                }
                            />
                            <Label htmlFor="is_active">Active</Label>
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
                            Save Changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
