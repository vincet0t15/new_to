import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import transportationModesRoutes from '@/routes/transportation-modes';
import type { TransportationMode } from '@/types/transportation-mode';

type Props = {
    transportationMode: TransportationMode;
};

export default function EditTransportationMode({ transportationMode }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: transportationMode.name ?? '',
        description: transportationMode.description ?? '',
        is_government: transportationMode.is_government ?? false,
        fix_cost: transportationMode.fix_cost ?? '',
        is_active: transportationMode.is_active ?? true,
    });

    const submit = () => {
        put(transportationModesRoutes.update(transportationMode).url);
    };

    return (
        <>
            <Head title="Edit Transportation Mode" />
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
                        title="Edit Transportation Mode"
                        description="Update the transportation mode details."
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">Name *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Input
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="fix_cost">Fix Cost</Label>
                        <Input
                            id="fix_cost"
                            type="number"
                            value={data.fix_cost}
                            onChange={(e) =>
                                setData('fix_cost', e.target.value)
                            }
                        />
                        <InputError message={errors.fix_cost} />
                    </div>
                    <div className="flex items-center gap-2 pt-6">
                        <input
                            id="is_government"
                            type="checkbox"
                            checked={data.is_government}
                            onChange={(e) =>
                                setData('is_government', e.target.checked)
                            }
                        />
                        <Label htmlFor="is_government">
                            Government vehicle
                        </Label>
                    </div>
                    <div className="flex items-center gap-2 pt-6">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                </div>

                <div className="flex justify-end">
                    <Can permission="transportation-modes.update">
                        <Button onClick={submit} disabled={processing}>
                            {processing && (
                                <Loader2 className="size-4 animate-spin" />
                            )}
                            <Save className="size-4" />
                            Save changes
                        </Button>
                    </Can>
                </div>
            </div>
        </>
    );
}

EditTransportationMode.layout = {
    breadcrumbs: [{ title: 'Edit Transportation Mode', href: '#' }],
};
