import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { WorkflowStepTypeRequest } from '@/types/workflow';
import type { FormEvent } from 'react';

type Props = { isOpen: boolean; onClose: () => void };

export function CreateStepTypeDialog({ isOpen, onClose }: Props) {
    const { data, setData, post, reset, errors, processing } =
        useForm<WorkflowStepTypeRequest>({
            name: '',
            signature_label: '',
            signature_title: '',
            is_signatory: true,
            sort_order: 0,
            is_active: true,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(workflowStepTypes.store().url, {
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
                        <DialogTitle>Create Step Type</DialogTitle>
                        <DialogDescription>
                            Define a reusable step meaning.
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
                                placeholder="e.g., Mayor"
                            />
                            <InputError message={errors.name} />
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
                                placeholder="e.g., Approved by"
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
                                placeholder="e.g., Department Head"
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
                                placeholder="e.g., 1"
                            />
                            <InputError message={errors.sort_order} />
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_signatory"
                                checked={data.is_signatory}
                                onCheckedChange={(checked) =>
                                    setData('is_signatory', checked === true)
                                }
                            />
                            <Label htmlFor="is_signatory">
                                Signatory (not just receipt)
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked === true)
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
                            Create Step Type
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
