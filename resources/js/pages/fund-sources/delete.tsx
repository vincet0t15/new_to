import { useForm } from '@inertiajs/react';
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
import { Spinner } from '@/components/ui/spinner';
import fundSourcesRoutes from '@/routes/fund-sources';
import type { FundSource } from '@/types/fund-source';
import type { FormEvent } from 'react';

type DeleteFundSourceDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    fundSource: FundSource;
};

export function DeleteFundSourceDialog({
    isOpen,
    onClose,
    fundSource,
}: DeleteFundSourceDialogProps) {
    const { delete: destroy, processing } = useForm();
    const isInUse = fundSource.usage_count > 0;

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        destroy(fundSourcesRoutes.destroy(fundSource).url, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-sm">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Delete Fund Source</DialogTitle>
                        <DialogDescription>
                            {isInUse ? (
                                <>
                                    "{fundSource.name}" is used by{' '}
                                    {fundSource.usage_count} record(s) and
                                    cannot be deleted. Set it to inactive
                                    instead.
                                </>
                            ) : (
                                <>
                                    Are you sure you want to delete "
                                    {fundSource.name}"? This action cannot be
                                    undone.
                                </>
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Close
                            </Button>
                        </DialogClose>
                        {!isInUse && (
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Delete Fund Source
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
