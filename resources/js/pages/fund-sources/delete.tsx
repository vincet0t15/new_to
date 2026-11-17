import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import fundSourcesRoutes from '@/routes/fund-sources';
import type { FundSource } from '@/types/travel-setup';

type Props = { fundSource: FundSource };

export default function DeleteFundSource({ fundSource }: Props) {
    const destroy = () => router.delete(fundSourcesRoutes.destroy(fundSource).url);
    return (
        <>
            <Head title="Delete Fund Source" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => router.visit(fundSourcesRoutes.index().url)}><ArrowLeft className="size-4" /> Back</Button>
                    <Heading title="Delete Fund Source" description="Confirm deletion." />
                </div>
                <div className="rounded-xl border p-4"><p className="text-sm">Are you sure you want to delete <span className="font-medium">{fundSource.name}</span>?</p></div>
                <div className="flex justify-end gap-2">
                    <Can permission="fund-sources.delete">
                        <Button variant="destructive" onClick={destroy}><Trash2 className="size-4" /> Delete</Button>
                    </Can>
                </div>
            </div>
        </>
    );
}
DeleteFundSource.layout = { breadcrumbs: [{ title: 'Delete Fund Source', href: '#' }] };
