import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import transportationModes from '@/routes/transportation-modes';
import type { TransportationMode } from '@/types/transportation-mode';

type DeleteTransportationModeProps = {
    transportationMode: TransportationMode;
};

/**
 * Confirm the deletion of a transportation mode.
 *
 * A page rather than a dialog so the usage count and any fares are visible
 * before confirming.
 */
export default function TransportationModesDelete({
    transportationMode,
}: DeleteTransportationModeProps) {
    const { delete: destroy, processing } = useForm();
    const isInUse = transportationMode.usage_count > 0;

    const handleSubmit = () => {
        destroy(transportationModes.destroy(transportationMode).url);
    };

    return (
        <>
            <Head title="Delete Transportation Mode" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Delete Transportation Mode"
                    description="This cannot be undone."
                />

                <Button
                    variant="ghost"
                    className="w-fit"
                    onClick={() => router.get(transportationModes.index().url)}
                >
                    <ArrowLeft />
                    Back to list
                </Button>

                <div className="w-full max-w-xl space-y-6 rounded-lg border bg-card p-6 shadow-sm">
                    {transportationMode.usage_count > 0 ? (
                        <Alert variant="destructive">
                            <AlertTriangle />
                            <AlertDescription>
                                "{transportationMode.name}" is used by
                                {transportationMode.usage_count} record(s) and
                                cannot be deleted. Set it to inactive instead.
                            </AlertDescription>
                        </Alert>
                    ) : (
                        <Alert>
                            <AlertTriangle />
                            <AlertDescription>
                                Are you sure you want to delete "
                                {transportationMode.name}"? Its destination
                                fares will be deleted too. This action cannot be
                                undone.
                            </AlertDescription>
                        </Alert>
                    )}

                    {transportationMode.fares.length > 0 && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium">
                                Fares that will be deleted
                            </p>
                            <ul className="divide-y rounded-md border text-sm">
                                {transportationMode.fares.map((fare) => (
                                    <li
                                        key={fare.id}
                                        className="flex items-center justify-between gap-4 px-3 py-2"
                                    >
                                        <span>
                                            {fare.location?.name ?? 'Unknown'}
                                        </span>
                                        <span className="text-muted-foreground tabular-nums">
                                            {Number(fare.amount).toLocaleString(
                                                'en-PH',
                                                {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2,
                                                },
                                            )}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <div className="flex justify-end gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.get(transportationModes.index().url)
                            }
                        >
                            Cancel
                        </Button>
                        {!isInUse && (
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={handleSubmit}
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Delete Transportation Mode
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

TransportationModesDelete.layout = {
    breadcrumbs: [
        {
            title: 'Transportation Modes',
            href: transportationModes.index().url,
        },
        {
            title: 'Delete',
            href: transportationModes.index().url,
        },
    ],
};
