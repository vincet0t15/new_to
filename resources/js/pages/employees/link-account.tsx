import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import employees from '@/routes/employees';
import type {
    Employee,
    EmployeeLoginFormData,
    EmployeeLoginOption,
} from '@/types/employee';
import type { FormEvent } from 'react';

type LinkAccountCardProps = {
    employee: Employee;
    linkedAccount: EmployeeLoginOption | null;
    accountOptions: EmployeeLoginOption[];
};

export function LinkAccountCard({
    employee,
    linkedAccount,
    accountOptions,
}: LinkAccountCardProps) {
    const { data, setData, transform, put, errors, processing } =
        useForm<EmployeeLoginFormData>({
            user_id: linkedAccount === null ? 'none' : String(linkedAccount.id),
        });

    const options = [
        { value: 'none', label: 'No login account' },
        ...accountOptions.map((account) => ({
            value: String(account.id),
            label: `${account.name} — @${account.username}`,
        })),
    ];

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        transform(() => ({
            user_id: data.user_id === 'none' ? null : Number(data.user_id),
        }));

        put(employees.account.update(employee).url, {
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={handleSubmit} className="rounded-xl border bg-card p-6">
            <div className="space-y-2">
                <h2 className="text-base font-semibold">Login Account</h2>
                <p className="text-sm text-muted-foreground">
                    Link the account that receives travel orders for this
                    employee. Choose “No login account” to remove the link.
                </p>
                <div className="flex w-full flex-col gap-1">
                    <Label htmlFor="user_id">Account</Label>
                    <Combobox
                        id="user_id"
                        value={data.user_id}
                        onValueChange={(value) => setData('user_id', value)}
                        options={options}
                        placeholder="No login account"
                        searchPlaceholder="Search accounts..."
                        emptyMessage="No accounts available."
                        className="w-full"
                    />
                    <p className="text-xs text-muted-foreground">
                        Only unlinked accounts and this employee's current
                        account are listed.
                    </p>
                    <InputError message={errors.user_id} />
                </div>
            </div>

            <div className="mt-4 flex justify-end">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    Save Account Link
                </Button>
            </div>
        </form>
    );
}
