import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import fundSourcesRoutes from '@/routes/fund-sources';
import type { FundSource } from '@/types/travel-setup';

type Props = { fundSource: FundSource };

export default function EditFundSource({ fundSource }: Props) {
    const { data, setData, put, processing, errors } = useForm({ name: fundSource.name ?? '', description: fundSource.description ?? '', is_active: fundSource.is_active ?? true });
    const submit = () => put(fundSourcesRoutes.update(fundSource).url);
    return (
        <>
            <Head title="Edit Fund Source" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => router.visit(fundSourcesRoutes.index().url)}><ArrowLeft className="size-4" /> Back</Button>
                    <Heading title="Edit Fund Source" description="Update the fund source details." />
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2"><Label htmlFor="name">Name *</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} /><InputError message={errors.name} /></div>
                    <div className="space-y-2"><Label htmlFor="description">Description</Label><Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} /><InputError message={errors.description} /></div>
                    <div className="flex items-center gap-2 pt-6"><input id="is_active" type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /><Label htmlFor="is_active">Active</Label></div>
                </div>
                <div className="flex justify-end">
                    <Can permission="fund-sources.update">
                        <Button onClick={submit} disabled={processing}>{processing && <Loader2 className="size-4 animate-spin" />}<Save className="size-4" /> Save changes</Button>
                    </Can>
                </div>
            </div>
        </>
    );
}
EditFundSource.layout = { breadcrumbs: [{ title: 'Edit Fund Source', href: '#' }] };
