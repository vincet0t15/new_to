import { router, useForm } from '@inertiajs/react';
import { Upload, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import employees from '@/routes/employees';
import type { Employee, EmployeeCreateRequest } from '@/types/employee';
import type { ChangeEvent, FormEvent, ReactNode } from 'react';

type LookupOption = {
    id: number;
    name: string;
};

type EmployeeFormProps = {
    offices: { id: number; office_name: string }[];
    positions: LookupOption[];
    employmentTypes: LookupOption[];
    employee?: Employee;
};

function Field({
    id,
    label,
    error,
    children,
}: {
    id: string;
    label: ReactNode;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex w-full flex-col gap-1">
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

function LookupSelect({
    id,
    label,
    value,
    options,
    placeholder,
    error,
    onChange,
}: {
    id: string;
    label: string;
    value: number | '';
    options: LookupOption[];
    placeholder: string;
    error?: string;
    onChange: (value: number | '') => void;
}) {
    return (
        <Field id={id} label={`${label} *`} error={error}>
            <Select
                value={value === '' ? undefined : String(value)}
                onValueChange={(next) =>
                    onChange(next === '' ? '' : Number(next))
                }
            >
                <SelectTrigger id={id} className="w-full">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.id} value={String(option.id)}>
                            {option.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </Field>
    );
}

export function EmployeeForm({
    offices,
    positions,
    employmentTypes,
    employee,
}: EmployeeFormProps) {
    const officeOptions: LookupOption[] = offices.map((office) => ({
        id: office.id,
        name: office.office_name,
    }));

    const { data, setData, post, put, errors, processing } =
        useForm<EmployeeCreateRequest>({
            first_name: employee?.first_name ?? '',
            middle_name: employee?.middle_name ?? '',
            last_name: employee?.last_name ?? '',
            suffix: employee?.suffix ?? '',
            position_id: employee?.position_id ?? '',
            office_id: employee?.office_id ?? '',
            employment_type_id: employee?.employment_type_id ?? '',
            contact_number: employee?.contact_number ?? '',
            monthly_salary: employee?.monthly_salary ?? '',
            is_active: employee?.is_active ?? true,
            photo: null,
            remove_photo: false,
        });

    const [photoPreview, setPhotoPreview] = useState<string | null>(
        employee?.photo_url ?? null,
    );
    const photoPreviewRef = useRef<string | null>(null);

    // Object URLs for a newly chosen file are ours to release. The employee's
    // stored photo is a plain URL and needs no cleanup.
    useEffect(() => {
        return () => {
            if (photoPreviewRef.current) {
                URL.revokeObjectURL(photoPreviewRef.current);
            }
        };
    }, []);

    const openPhotoPicker = () => {
        document.getElementById('photo')?.click();
    };

    const clearObjectUrl = () => {
        if (photoPreviewRef.current) {
            URL.revokeObjectURL(photoPreviewRef.current);
            photoPreviewRef.current = null;
        }
    };

    const handlePhotoChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        clearObjectUrl();

        if (file) {
            const objectUrl = URL.createObjectURL(file);
            photoPreviewRef.current = objectUrl;
            setPhotoPreview(objectUrl);
            setData('remove_photo', false);
        } else {
            setPhotoPreview(employee?.photo_url ?? null);
        }

        setData('photo', file);
    };

    const handleRemovePhoto = (checked: boolean) => {
        setData('remove_photo', checked);

        if (checked) {
            clearObjectUrl();
            setPhotoPreview(null);
            setData('photo', null);

            const input = document.getElementById(
                'photo',
            ) as HTMLInputElement | null;

            if (input) {
                input.value = '';
            }
        } else {
            setPhotoPreview(employee?.photo_url ?? null);
        }
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = {
            forceFormData: true,
            preserveScroll: true,
        };

        if (employee) {
            put(employees.update(employee).url, options);
        } else {
            post(employees.store().url, options);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="rounded-xl border bg-card p-6">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="flex flex-col items-center space-y-4">
                        <input
                            id="photo"
                            name="photo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            className="hidden"
                            onChange={handlePhotoChange}
                        />

                        <button
                            type="button"
                            className="relative flex aspect-square w-full max-w-xs cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-background"
                            onClick={openPhotoPicker}
                        >
                            {photoPreview ? (
                                <img
                                    src={photoPreview}
                                    alt="Employee photo preview"
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <div className="flex flex-col items-center justify-center gap-2 p-4 text-center">
                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                                        <Upload className="size-5 text-muted-foreground" />
                                    </div>
                                    <div className="text-sm font-semibold">
                                        Upload Photo
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        Click to browse
                                    </div>
                                </div>
                            )}
                        </button>

                        <div className="flex flex-wrap justify-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={openPhotoPicker}
                            >
                                <Upload className="mr-1 size-4" />
                                {photoPreview ? 'Change' : 'Choose'}
                            </Button>

                            {photoPreview && (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    onClick={() => {
                                        handleRemovePhoto(true);
                                    }}
                                >
                                    <X className="mr-1 size-4" />
                                    Remove
                                </Button>
                            )}
                        </div>

                        {employee?.image_path && (
                            <label className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Checkbox
                                    checked={data.remove_photo}
                                    onCheckedChange={(checked) =>
                                        handleRemovePhoto(checked === true)
                                    }
                                />
                                Remove the current photo
                            </label>
                        )}

                        <p className="text-center text-xs text-muted-foreground">
                            jpeg, jpg, png, webp (max 2MB)
                        </p>

                        <InputError message={errors.photo} />
                    </div>

                    <div className="space-y-4 md:col-span-2">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Field
                                id="first_name"
                                label="First Name *"
                                error={errors.first_name}
                            >
                                <Input
                                    id="first_name"
                                    name="first_name"
                                    value={data.first_name}
                                    onChange={(event) =>
                                        setData(
                                            'first_name',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="First Name"
                                />
                            </Field>

                            <Field
                                id="middle_name"
                                label="Middle Name"
                                error={errors.middle_name}
                            >
                                <Input
                                    id="middle_name"
                                    name="middle_name"
                                    value={data.middle_name}
                                    onChange={(event) =>
                                        setData(
                                            'middle_name',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Middle Name"
                                />
                            </Field>
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Field
                                id="last_name"
                                label="Last Name *"
                                error={errors.last_name}
                            >
                                <Input
                                    id="last_name"
                                    name="last_name"
                                    value={data.last_name}
                                    onChange={(event) =>
                                        setData('last_name', event.target.value)
                                    }
                                    placeholder="Last Name"
                                />
                            </Field>

                            <Field
                                id="suffix"
                                label="Suffix"
                                error={errors.suffix}
                            >
                                <Input
                                    id="suffix"
                                    name="suffix"
                                    value={data.suffix}
                                    onChange={(event) =>
                                        setData('suffix', event.target.value)
                                    }
                                    placeholder="e.g., Jr., III"
                                />
                            </Field>
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <LookupSelect
                                id="position_id"
                                label="Position"
                                value={data.position_id}
                                options={positions}
                                placeholder="Select Position"
                                error={errors.position_id}
                                onChange={(value) =>
                                    setData('position_id', value)
                                }
                            />

                            <LookupSelect
                                id="office_id"
                                label="Office"
                                value={data.office_id}
                                options={officeOptions}
                                placeholder="Select Office"
                                error={errors.office_id}
                                onChange={(value) =>
                                    setData('office_id', value)
                                }
                            />
                        </div>

                        <LookupSelect
                            id="employment_type_id"
                            label="Employment Type"
                            value={data.employment_type_id}
                            options={employmentTypes}
                            placeholder="Select Employment Type"
                            error={errors.employment_type_id}
                            onChange={(value) =>
                                setData('employment_type_id', value)
                            }
                        />

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Field
                                id="contact_number"
                                label="Contact Number"
                                error={errors.contact_number}
                            >
                                <Input
                                    id="contact_number"
                                    name="contact_number"
                                    value={data.contact_number}
                                    onChange={(event) =>
                                        setData(
                                            'contact_number',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="e.g., 0917 123 4567"
                                />
                            </Field>

                            <Field
                                id="monthly_salary"
                                label="Monthly Salary (₱)"
                                error={errors.monthly_salary}
                            >
                                <Input
                                    id="monthly_salary"
                                    name="monthly_salary"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.monthly_salary}
                                    onChange={(event) =>
                                        setData(
                                            'monthly_salary',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="e.g., 25000.00"
                                />
                            </Field>
                        </div>

                        <div className="flex w-full flex-col gap-1">
                            <Label htmlFor="is_active">Active Employee</Label>
                            <div className="flex h-9 items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="is_active"
                                    className="font-normal"
                                >
                                    {data.is_active ? 'Active' : 'Inactive'}
                                </Label>
                            </div>
                            <InputError message={errors.is_active} />
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => router.get(employees.index().url)}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {employee ? 'Save Changes' : 'Create Employee'}
                </Button>
            </div>
        </form>
    );
}
