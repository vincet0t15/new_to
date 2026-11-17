import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import travelClassificationsRoutes from '@/routes/travel-classifications';
import type { TravelClassification } from '@/types/travel-setup';

type Props = { travelClassification: TravelClassification };

export default function DeleteTravelClassification({ travelClassification }: Props) {
    const destroy = () => router.delete(travelClassificationsRoutes.destroy(travelClassification).url);
    return (
        <>
            <Head title="Delete Travel Classification" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => router.visit(travelClassificationsRoutes.index().url)}><ArrowLeft className="size-4" /> Back</Button>
                    <Heading title="Delete Travel Classification" description="Confirm deletion." />
                </div>
                <div className="rounded-xl border p-4"><p className="text-sm">Are you sure you want to delete <span className="font-medium">{travelClassification.name}</span>?</p></div>
                <div className="flex justify-end gap-2">
                    <Can permission="travel-classifications.delete">
                        <Button variant="destructive" onClick={destroy}><Trash2 className="size-4" /> Delete</Button>
                    </Can>
                </div>
            </div>
        </>
    );
}
DeleteTravelClassification.layout = { breadcrumbs: [{ title: 'Delete Travel Classification', href: '#' }] };
