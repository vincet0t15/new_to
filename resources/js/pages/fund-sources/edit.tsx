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
import { Textarea } from '@/components/ui/textarea';
import fundSourcesRoutes from '@/routes/fund-sources';
import type { FundSource, FundSourceCreateRequest } from '@/types/fund-source';
import type { FormEvent } from 'react';

type EditFundSourceDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    fundSource: FundSource;
};

export function EditFundSourceDialog({
    isOpen,
    onClose,
    fundSource,
}: EditFundSourceDialogProps) {
    const { data, setData, put, errors, processing } =
        useForm<FundSourceCreateRequest>({
            name: fundSource.name,
            description: fundSource.description ?? '',
            is_active: fundSource.is_active,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(fundSourcesRoutes.update(fundSource).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Fund Source</DialogTitle>
                        <DialogDescription>
                            Update the fundSource details.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
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

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                name="description"
                                rows={3}
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                                placeholder="Optional description"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked === true)
                                }
                            />
                            <Label htmlFor="is_active">
                                Active (available for selection)
                            </Label>
                        </div>

                        {fundSource.usage_count > 0 && (
                            <p className="text-sm text-muted-foreground">
                                This fundSource is used by{' '}
                                {fundSource.usage_count} record(s), so it cannot
                                be deleted. Set it to inactive to hide it from
                                new entries.
                            </p>
                        )}
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
