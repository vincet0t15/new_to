import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import locationsRoutes from '@/routes/locations';
import type { Location } from '@/types/travel-setup';

type Props = { location: Location };

export default function DeleteLocation({ location }: Props) {
    const destroy = () => router.delete(locationsRoutes.destroy(location).url);
    return (
        <>
            <Head title="Delete Location" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => router.visit(locationsRoutes.index().url)}><ArrowLeft className="size-4" /> Back</Button>
                    <Heading title="Delete Location" description="Confirm deletion." />
                </div>
                <div className="rounded-xl border p-4"><p className="text-sm">Are you sure you want to delete <span className="font-medium">{location.name}</span>?</p></div>
                <div className="flex justify-end gap-2">
                    <Can permission="locations.delete">
                        <Button variant="destructive" onClick={destroy}><Trash2 className="size-4" /> Delete</Button>
                    </Can>
                </div>
            </div>
        </>
    );
}
DeleteLocation.layout = { breadcrumbs: [{ title: 'Delete Location', href: '#' }] };
