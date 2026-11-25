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
import type { OfficeCreateRequest } from '@/types/office';
import type { FormEvent } from 'react';

type CreateOfficeDialogProps = {
    isOpen: boolean;
    onClose: () => void;
};

export function CreateOfficeDialog({
    isOpen,
    onClose,
}: CreateOfficeDialogProps) {
    const { data, setData, post, reset, errors, processing } =
        useForm<OfficeCreateRequest>({
            office_name: '',
            office_code: '',
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
                                value={data.office_code}
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
                            Create Office
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
