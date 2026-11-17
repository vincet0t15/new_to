import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import perDiemConfigurationsRoutes from '@/routes/per-diem-configurations';
import type { PerDiemConfiguration } from '@/types/travel-setup';

type Props = { perDiemConfiguration: PerDiemConfiguration };

export default function DeletePerDiemConfiguration({ perDiemConfiguration }: Props) {
    const destroy = () => router.delete(perDiemConfigurationsRoutes.destroy(perDiemConfiguration).url);
    return (
        <>
            <Head title="Delete Per Diem Configuration" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => router.visit(perDiemConfigurationsRoutes.index().url)}><ArrowLeft className="size-4" /> Back</Button>
                    <Heading title="Delete Per Diem Configuration" description="Confirm deletion." />
                </div>
                <div className="rounded-xl border p-4"><p className="text-sm">Are you sure you want to delete <span className="font-medium">{perDiemConfiguration.name}</span>?</p></div>
                <div className="flex justify-end gap-2">
                    <Can permission="per-diem-configurations.delete">
                        <Button variant="destructive" onClick={destroy}><Trash2 className="size-4" /> Delete</Button>
                    </Can>
                </div>
            </div>
        </>
    );
}
DeletePerDiemConfiguration.layout = { breadcrumbs: [{ title: 'Delete Per Diem Configuration', href: '#' }] };
