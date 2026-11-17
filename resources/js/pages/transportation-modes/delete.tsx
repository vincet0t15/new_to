import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import transportationModesRoutes from '@/routes/transportation-modes';
import type { TransportationMode } from '@/types/transportation-mode';

type Props = {
    transportationMode: TransportationMode;
    usageCount: number;
};

export default function DeleteTransportationMode({
    transportationMode,
    usageCount,
}: Props) {
    const destroy = () => {
        router.delete(
            transportationModesRoutes.destroy(transportationMode).url,
        );
    };

    return (
        <>
            <Head title="Delete Transportation Mode" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            router.visit(transportationModesRoutes.index().url)
                        }
                    >
                        <ArrowLeft className="size-4" />
                        Back
                    </Button>
                    <Heading
                        title="Delete Transportation Mode"
                        description="Confirm deletion."
                    />
                </div>

                <div className="rounded-xl border p-4">
                    <p className="text-sm">
                        Are you sure you want to delete{' '}
                        <span className="font-medium">
                            {transportationMode.name}
                        </span>
                        ?
                    </p>
                    {usageCount > 0 && (
                        <p className="mt-2 text-sm text-destructive">
                            This mode is referenced by {usageCount} travel
                            order(s) and cannot be deleted. Set it to inactive
                            instead.
                        </p>
                    )}
                </div>

                <div className="flex justify-end gap-2">
                    <Can permission="transportation-modes.delete">
                        <Button
                            variant="destructive"
                            onClick={destroy}
                            disabled={usageCount > 0}
                        >
                            <Trash2 className="size-4" />
                            Delete
                        </Button>
                    </Can>
                </div>
            </div>
        </>
    );
}

DeleteTransportationMode.layout = {
    breadcrumbs: [{ title: 'Delete Transportation Mode', href: '#' }],
};
