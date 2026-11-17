import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import transportationModesRoutes from '@/routes/transportation-modes';
import type { TransportationModeCreateRequest } from '@/types/transportation-mode';

export default function CreateTransportationMode() {
    const { data, setData, post, processing, errors } =
        useForm<TransportationModeCreateRequest>({
            name: '',
            description: '',
            is_government: false,
            fix_cost: '',
            is_active: true,
        });

    const submit = () => {
        post(transportationModesRoutes.store().url);
    };

    return (
        <>
            <Head title="Create Transportation Mode" />
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
                        title="Create Transportation Mode"
                        description="Add a new mode of transport."
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
                    <Can permission="transportation-modes.create">
                        <Button onClick={submit} disabled={processing}>
                            {processing && (
                                <Loader2 className="size-4 animate-spin" />
                            )}
                            <Save className="size-4" />
                            Save
                        </Button>
                    </Can>
                </div>
            </div>
        </>
    );
}

CreateTransportationMode.layout = {
    breadcrumbs: [{ title: 'Create Transportation Mode', href: '#' }],
};
