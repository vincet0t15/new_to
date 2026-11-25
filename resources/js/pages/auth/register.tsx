import { Head, useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';
import type { FormEvent } from 'react';

type LookupOption = {
    id: number;
    name: string;
};

type OfficeOption = {
    id: number;
    office_name: string;
};

type Props = {
    passwordRules: string;
    offices: OfficeOption[];
    positions: LookupOption[];
    employmentTypes: LookupOption[];
};

type RegisterFormData = {
    first_name: string;
    middle_name: string;
    last_name: string;
    suffix: string;
    position_id: number | '';
    office_id: number | '';
    employment_type_id: number | '';
    contact_number: string;
    monthly_salary: string;
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
    is_active: boolean;
};

function Field({
    id,
    label,
    error,
    className,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('grid gap-1', className)}>
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

function SectionHeading({ id, children }: { id: string; children: ReactNode }) {
    return (
        <h2
            id={id}
            className="text-sm font-semibold tracking-tight text-slate-900 dark:text-slate-100"
        >
            {children}
        </h2>
    );
}

export default function Register({
    passwordRules,
    offices,
    positions,
    employmentTypes,
}: Props) {
    const { data, setData, post, processing, errors, reset } =
        useForm<RegisterFormData>({
            first_name: '',
            middle_name: '',
            last_name: '',
            suffix: '',
            position_id: '',
            office_id: '',
            employment_type_id: '',
            contact_number: '',
            monthly_salary: '',
            username: '',
            email: '',
            password: '',
            password_confirmation: '',
            is_active: true,
        });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(store().url, {
            onSuccess: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Register" />
            <form onSubmit={handleSubmit} className="space-y-2">
                <section
                    aria-labelledby="register-personal-heading"
                    className="space-y-2"
                >
                    <SectionHeading id="register-personal-heading">
                        Personal details
                    </SectionHeading>
                    <div className="grid gap-2 sm:grid-cols-2">
                        <Field
                            id="first_name"
                            label="First name *"
                            error={errors.first_name}
                        >
                            <Input
                                id="first_name"
                                type="text"
                                required
                                autoFocus
                                autoComplete="given-name"
                                value={data.first_name}
                                onChange={(event) =>
                                    setData('first_name', event.target.value)
                                }
                                placeholder="e.g., Juan"
                            />
                        </Field>

                        <Field
                            id="middle_name"
                            label="Middle name"
                            error={errors.middle_name}
                        >
                            <Input
                                id="middle_name"
                                type="text"
                                autoComplete="additional-name"
                                value={data.middle_name}
                                onChange={(event) =>
                                    setData('middle_name', event.target.value)
                                }
                                placeholder="e.g., Santos"
                            />
                        </Field>

                        <Field
                            id="last_name"
                            label="Last name *"
                            error={errors.last_name}
                        >
                            <Input
                                id="last_name"
                                type="text"
                                required
                                autoComplete="family-name"
                                value={data.last_name}
                                onChange={(event) =>
                                    setData('last_name', event.target.value)
                                }
                                placeholder="e.g., Dela Cruz"
                            />
                        </Field>

                        <Field id="suffix" label="Suffix" error={errors.suffix}>
                            <Input
                                id="suffix"
                                type="text"
                                value={data.suffix}
                                onChange={(event) =>
                                    setData('suffix', event.target.value)
                                }
                                placeholder="e.g., Jr."
                            />
                        </Field>
                    </div>
                </section>

                <section
                    aria-labelledby="register-position-heading"
                    className="space-y-2 border-t border-slate-200 pt-3 dark:border-slate-800"
                >
                    <SectionHeading id="register-position-heading">
                        Position and contact
                    </SectionHeading>
                    <div className="grid gap-2 sm:grid-cols-2">
                        <Field
                            id="position_id"
                            label="Position *"
                            error={errors.position_id}
                        >
                            <Combobox
                                id="position_id"
                                value={
                                    data.position_id === ''
                                        ? ''
                                        : String(data.position_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'position_id',
                                        value === '' ? '' : Number(value),
                                    )
                                }
                                options={positions.map((position) => ({
                                    value: String(position.id),
                                    label: position.name,
                                }))}
                                placeholder="Select position"
                                searchPlaceholder="Search positions..."
                                emptyMessage="No positions found."
                            />
                        </Field>

                        <Field
                            id="office_id"
                            label="Office *"
                            error={errors.office_id}
                        >
                            <Combobox
                                id="office_id"
                                value={
                                    data.office_id === ''
                                        ? ''
                                        : String(data.office_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'office_id',
                                        value === '' ? '' : Number(value),
                                    )
                                }
                                options={offices.map((office) => ({
                                    value: String(office.id),
                                    label: office.office_name,
                                }))}
                                placeholder="Select office"
                                searchPlaceholder="Search offices..."
                                emptyMessage="No offices found."
                            />
                        </Field>

                        <Field
                            id="employment_type_id"
                            label="Employment type *"
                            error={errors.employment_type_id}
                        >
                            <Combobox
                                id="employment_type_id"
                                value={
                                    data.employment_type_id === ''
                                        ? ''
                                        : String(data.employment_type_id)
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'employment_type_id',
                                        value === '' ? '' : Number(value),
                                    )
                                }
                                options={employmentTypes.map((type) => ({
                                    value: String(type.id),
                                    label: type.name,
                                }))}
                                placeholder="Select employment type"
                                searchPlaceholder="Search employment types..."
                                emptyMessage="No employment types found."
                            />
                        </Field>

                        <Field
                            id="contact_number"
                            label="Contact number"
                            error={errors.contact_number}
                        >
                            <Input
                                id="contact_number"
                                type="text"
                                autoComplete="tel"
                                value={data.contact_number}
                                onChange={(event) =>
                                    setData(
                                        'contact_number',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g., 09123456789"
                            />
                        </Field>

                        <Field
                            id="monthly_salary"
                            label="Monthly salary (₱)"
                            error={errors.monthly_salary}
                            className="sm:col-span-2"
                        >
                            <Input
                                id="monthly_salary"
                                type="number"
                                min="0"
                                step="0.01"
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
                </section>

                <section
                    aria-labelledby="register-account-heading"
                    className="space-y-2 border-t border-slate-200 pt-3 dark:border-slate-800"
                >
                    <SectionHeading id="register-account-heading">
                        Account access
                    </SectionHeading>
                    <div className="grid gap-2 sm:grid-cols-2">
                        <Field
                            id="username"
                            label="Username *"
                            error={errors.username}
                        >
                            <Input
                                id="username"
                                type="text"
                                required
                                autoComplete="username"
                                value={data.username}
                                onChange={(event) =>
                                    setData('username', event.target.value)
                                }
                                placeholder="Enter username"
                            />
                        </Field>

                        <Field id="email" label="Email" error={errors.email}>
                            <Input
                                id="email"
                                type="email"
                                autoComplete="email"
                                value={data.email}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                placeholder="e.g., name@example.com"
                            />
                        </Field>

                        <Field
                            id="password"
                            label="Password *"
                            error={errors.password}
                        >
                            <PasswordInput
                                id="password"
                                required
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                                placeholder="Password"
                                passwordrules={passwordRules}
                            />
                        </Field>

                        <Field
                            id="password_confirmation"
                            label="Confirm password *"
                            error={errors.password_confirmation}
                        >
                            <PasswordInput
                                id="password_confirmation"
                                required
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(event) =>
                                    setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                                placeholder="Confirm password"
                                passwordrules={passwordRules}
                            />
                        </Field>

                        <div className="flex items-start gap-2 sm:col-span-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked === true)
                                }
                            />
                            <div className="space-y-1">
                                <Label htmlFor="is_active">
                                    Active account
                                </Label>
                                <p className="text-xs text-muted-foreground">
                                    Inactive accounts cannot sign in until an
                                    administrator activates them.
                                </p>
                                <InputError message={errors.is_active} />
                            </div>
                        </div>
                    </div>
                </section>

                <div className="flex flex-col gap-2 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <Button
                        type="submit"
                        className="w-full sm:w-auto"
                        disabled={processing}
                        data-test="register-user-button"
                    >
                        {processing && <Spinner />}
                        <span>Create account</span>
                        <ArrowRight className="size-4" />
                    </Button>
                    <div className="text-center text-sm text-slate-600 sm:text-right dark:text-slate-400">
                        Already have an account?{' '}
                        <TextLink href={login()}>Log in</TextLink>
                    </div>
                </div>
            </form>
        </>
    );
}

Register.layout = {
    title: 'Create an employee account',
    description:
        'Register with your employee details. Approving roles are assigned separately by an administrator.',
    wide: true,
};
