import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { OfficePicker } from '@/components/office-picker';
import { OptionSelect } from '@/components/option-select';
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
import signatoryGroupsRoutes from '@/routes/signatory-groups';
import type { OfficeSelectOption } from '@/types/office';
import type {
    SignatoryGroup,
    SignatoryGroupForm,
} from '@/types/signatory-group';
import type { SelectOption } from '@/types/ui';
import type { FormEvent } from 'react';

type EditSignatoryGroupDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    signatoryGroup: SignatoryGroup;
    employeeOptions: SelectOption[];
    offices: OfficeSelectOption[];
};

export function EditSignatoryGroupDialog({
    isOpen,
    onClose,
    signatoryGroup,
    employeeOptions,
    offices,
}: EditSignatoryGroupDialogProps) {
    const { data, setData, put, errors, processing } =
        useForm<SignatoryGroupForm>({
            name: signatoryGroup.name,
            certified_signatory_employee_id:
                signatoryGroup.certified_signatory_employee_id ?? '',
            certified_signatory_title:
                signatoryGroup.certified_signatory_title ?? '',
            approved_signatory_employee_id:
                signatoryGroup.approved_signatory_employee_id ?? '',
            approved_signatory_title:
                signatoryGroup.approved_signatory_title ?? '',
            oic_signatory_employee_id:
                signatoryGroup.oic_signatory_employee_id ?? '',
            oic_signatory_title: signatoryGroup.oic_signatory_title ?? '',
            office_ids: signatoryGroup.offices.map((office) => office.id),
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(signatoryGroupsRoutes.update(signatoryGroup).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            Edit Travel Order Signatory Group
                        </DialogTitle>
                        <DialogDescription>
                            Update the group's name, the signer printed on each
                            line, and the offices that print it.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-6 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                                autoFocus
                                placeholder="Enter name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="rounded-xl border p-4">
                            <OfficePicker
                                offices={offices}
                                value={data.office_ids}
                                onChange={(officeIds) =>
                                    setData('office_ids', officeIds)
                                }
                                hint="Tick every office whose printed travel orders use this group's CERTIFIED, APPROVED, and OIC signers."
                                error={errors.office_ids}
                            />
                        </div>

                        <div className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-sm font-semibold">
                                CERTIFIED AS TO AVAILABILITY OF APPROPRIATION
                            </h3>
                            <OptionSelect
                                id="certified_signatory_employee_id"
                                label="Signer"
                                options={employeeOptions}
                                value={data.certified_signatory_employee_id}
                                onChange={(value) =>
                                    setData(
                                        'certified_signatory_employee_id',
                                        value,
                                    )
                                }
                                placeholder="Use the current designation..."
                                error={errors.certified_signatory_employee_id}
                                allowEmpty
                            />
                            <div className="space-y-2">
                                <Label htmlFor="certified_signatory_title">
                                    Title printed (optional)
                                </Label>
                                <Input
                                    id="certified_signatory_title"
                                    value={data.certified_signatory_title}
                                    onChange={(event) =>
                                        setData(
                                            'certified_signatory_title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="e.g., Municipal Mayor"
                                />
                                <InputError
                                    message={errors.certified_signatory_title}
                                />
                            </div>
                        </div>

                        <div className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-sm font-semibold">APPROVED</h3>
                            <OptionSelect
                                id="approved_signatory_employee_id"
                                label="Signer"
                                options={employeeOptions}
                                value={data.approved_signatory_employee_id}
                                onChange={(value) =>
                                    setData(
                                        'approved_signatory_employee_id',
                                        value,
                                    )
                                }
                                placeholder="Use the current designation..."
                                error={errors.approved_signatory_employee_id}
                                allowEmpty
                            />
                            <div className="space-y-2">
                                <Label htmlFor="approved_signatory_title">
                                    Title printed (optional)
                                </Label>
                                <Input
                                    id="approved_signatory_title"
                                    value={data.approved_signatory_title}
                                    onChange={(event) =>
                                        setData(
                                            'approved_signatory_title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="e.g., Department Head"
                                />
                                <InputError
                                    message={errors.approved_signatory_title}
                                />
                            </div>
                        </div>

                        <div className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-sm font-semibold">OIC</h3>
                            <OptionSelect
                                id="oic_signatory_employee_id"
                                label="Signer"
                                options={employeeOptions}
                                value={data.oic_signatory_employee_id}
                                onChange={(value) =>
                                    setData('oic_signatory_employee_id', value)
                                }
                                placeholder="Use the current designation..."
                                error={errors.oic_signatory_employee_id}
                                allowEmpty
                            />
                            <div className="space-y-2">
                                <Label htmlFor="oic_signatory_title">
                                    Title printed (optional)
                                </Label>
                                <Input
                                    id="oic_signatory_title"
                                    value={data.oic_signatory_title}
                                    onChange={(event) =>
                                        setData(
                                            'oic_signatory_title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="e.g., Officer-in-Charge"
                                />
                                <InputError
                                    message={errors.oic_signatory_title}
                                />
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
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
