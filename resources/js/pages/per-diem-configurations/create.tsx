import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import perDiemConfigurationsRoutes from '@/routes/per-diem-configurations';

export default function CreatePerDiemConfiguration() {
    const { data, setData, post, processing, errors } = useForm({ name: '', effective_from: '', effective_until: '', daily_rate: '', is_active: true });
    const submit = () => post(perDiemConfigurationsRoutes.store().url);
    return (
        <>
            <Head title="Create Per Diem Configuration" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => router.visit(perDiemConfigurationsRoutes.index().url)}><ArrowLeft className="size-4" /> Back</Button>
                    <Heading title="Create Per Diem Configuration" description="Add a new per diem configuration." />
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2"><Label htmlFor="name">Name *</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} /><InputError message={errors.name} /></div>
                    <div className="space-y-2"><Label htmlFor="effective_from">Effective From</Label><Input id="effective_from" type="date" value={data.effective_from} onChange={(e) => setData('effective_from', e.target.value)} /><InputError message={errors.effective_from} /></div>
                    <div className="space-y-2"><Label htmlFor="effective_until">Effective Until</Label><Input id="effective_until" type="date" value={data.effective_until} onChange={(e) => setData('effective_until', e.target.value)} /><InputError message={errors.effective_until} /></div>
                    <div className="space-y-2"><Label htmlFor="daily_rate">Daily Rate</Label><Input id="daily_rate" type="number" value={data.daily_rate} onChange={(e) => setData('daily_rate', e.target.value)} /><InputError message={errors.daily_rate} /></div>
                    <div className="flex items-center gap-2 pt-6"><input id="is_active" type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /><Label htmlFor="is_active">Active</Label></div>
                </div>
                <div className="flex justify-end">
                    <Can permission="per-diem-configurations.create">
                        <Button onClick={submit} disabled={processing}>{processing && <Loader2 className="size-4 animate-spin" />}<Save className="size-4" /> Save</Button>
                    </Can>
                </div>
            </div>
        </>
    );
}
CreatePerDiemConfiguration.layout = { breadcrumbs: [{ title: 'Create Per Diem Configuration', href: '#' }] };
