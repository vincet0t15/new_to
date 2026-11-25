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
import offices from '@/routes/offices';
import type { Office, OfficeUpdateRequest } from '@/types/office';
import type { FormEvent } from 'react';

type EditOfficeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    office: Office;
};

export function EditOfficeDialog({
    isOpen,
    onClose,
    office,
}: EditOfficeDialogProps) {
    const { data, setData, put, errors, processing } =
        useForm<OfficeUpdateRequest>({
            office_name: office.office_name,
            office_code: office.office_code ?? '',
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(offices.update(office).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Office</DialogTitle>
                        <DialogDescription>
                            Update the office details.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="office_name">Office Name *</Label>
                            <Input
                                id="office_name"
                                name="office_name"
                                value={data.office_name}
                                onChange={(event) =>
                                    setData('office_name', event.target.value)
                                }
                                required
                                autoFocus
                                placeholder="e.g., Municipal Planning Office"
                            />
                            <InputError message={errors.office_name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="office_code">Office Code</Label>
                            <Input
                                id="office_code"
                                name="office_code"
                                value={data.office_code ?? ''}
                                onChange={(event) =>
                                    setData('office_code', event.target.value)
                                }
                                placeholder="e.g., MPO"
                            />
                            <p className="text-xs text-muted-foreground">
                                Optional. A short code used on reports and
                                records.
                            </p>
                            <InputError message={errors.office_code} />
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
