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
import itinerarySignatoryGroupsRoutes from '@/routes/itinerary-signatory-groups';
import type { ItinerarySignatoryGroupForm } from '@/types/itinerary-signatory-group';
import type { OfficeSelectOption } from '@/types/office';
import type { SelectOption } from '@/types/ui';
import type { FormEvent } from 'react';

type CreateItinerarySignatoryGroupDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    employeeOptions: SelectOption[];
    offices: OfficeSelectOption[];
};

export function CreateItinerarySignatoryGroupDialog({
    isOpen,
    onClose,
    employeeOptions,
    offices,
}: CreateItinerarySignatoryGroupDialogProps) {
    const { data, setData, post, reset, errors, processing } =
        useForm<ItinerarySignatoryGroupForm>({
            name: '',
            certified_employee_id: '',
            certified_title: '',
            approved_employee_id: '',
            approved_title: '',
            office_ids: [],
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(itinerarySignatoryGroupsRoutes.store().url, {
            onSuccess: () => {
                onClose();
                reset();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            Create Itinerary Signatory Group
                        </DialogTitle>
                        <DialogDescription>
                            Name a set of itinerary signatories — for example,
                            the Mayor's Office or the Municipal Accountant —
                            pick the signer and printed title for each line,
                            then tick the offices that print it.
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
                                hint="Tick every office whose Appendix A itinerary uses this group's CERTIFIED/REVIEWED and APPROVED signers."
                                error={errors.office_ids}
                            />
                        </div>

                        <div className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-sm font-semibold">
                                CERTIFIED/REVIEWED BY
                            </h3>
                            <OptionSelect
                                id="certified_employee_id"
                                label="Signer"
                                options={employeeOptions}
                                value={data.certified_employee_id}
                                onChange={(value) =>
                                    setData('certified_employee_id', value)
                                }
                                placeholder="Use the usual signer..."
                                error={errors.certified_employee_id}
                                allowEmpty
                            />
                            <div className="space-y-2">
                                <Label htmlFor="certified_title">
                                    Title printed (optional)
                                </Label>
                                <Input
                                    id="certified_title"
                                    value={data.certified_title}
                                    onChange={(event) =>
                                        setData(
                                            'certified_title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="e.g., Municipal Mayor"
                                />
                                <InputError message={errors.certified_title} />
                            </div>
                        </div>

                        <div className="space-y-4 rounded-xl border p-4">
                            <h3 className="text-sm font-semibold">
                                APPROVED BY
                            </h3>
                            <OptionSelect
                                id="approved_employee_id"
                                label="Signer"
                                options={employeeOptions}
                                value={data.approved_employee_id}
                                onChange={(value) =>
                                    setData('approved_employee_id', value)
                                }
                                placeholder="Use the usual signer..."
                                error={errors.approved_employee_id}
                                allowEmpty
                            />
                            <div className="space-y-2">
                                <Label htmlFor="approved_title">
                                    Title printed (optional)
                                </Label>
                                <Input
                                    id="approved_title"
                                    value={data.approved_title}
                                    onChange={(event) =>
                                        setData(
                                            'approved_title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="e.g., Department Head"
                                />
                                <InputError message={errors.approved_title} />
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
                            Create Group
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
