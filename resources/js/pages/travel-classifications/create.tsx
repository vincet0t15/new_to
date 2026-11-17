import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import travelClassificationsRoutes from '@/routes/travel-classifications';

export default function CreateTravelClassification() {
    const { data, setData, post, processing, errors } = useForm({ name: '', description: '', is_active: true });
    const submit = () => post(travelClassificationsRoutes.store().url);
    return (
        <>
            <Head title="Create Travel Classification" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => router.visit(travelClassificationsRoutes.index().url)}><ArrowLeft className="size-4" /> Back</Button>
                    <Heading title="Create Travel Classification" description="Add a new travel classification." />
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2"><Label htmlFor="name">Name *</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} /><InputError message={errors.name} /></div>
                    <div className="space-y-2"><Label htmlFor="description">Description</Label><Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} /><InputError message={errors.description} /></div>
                    <div className="flex items-center gap-2 pt-6"><input id="is_active" type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /><Label htmlFor="is_active">Active</Label></div>
                </div>
                <div className="flex justify-end">
                    <Can permission="travel-classifications.create">
                        <Button onClick={submit} disabled={processing}>{processing && <Loader2 className="size-4 animate-spin" />}<Save className="size-4" /> Save</Button>
                    </Can>
                </div>
            </div>
        </>
    );
}
CreateTravelClassification.layout = { breadcrumbs: [{ title: 'Create Travel Classification', href: '#' }] };
