import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { Can } from '@/components/can';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import expenseTypesRoutes from '@/routes/expense-types';
import type { ExpenseType } from '@/types/travel-setup';

type Props = { expenseType: ExpenseType };

export default function DeleteExpenseType({ expenseType }: Props) {
    const destroy = () => router.delete(expenseTypesRoutes.destroy(expenseType).url);
    return (
        <>
            <Head title="Delete Expense Type" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => router.visit(expenseTypesRoutes.index().url)}><ArrowLeft className="size-4" /> Back</Button>
                    <Heading title="Delete Expense Type" description="Confirm deletion." />
                </div>
                <div className="rounded-xl border p-4">
                    <p className="text-sm">Are you sure you want to delete <span className="font-medium">{expenseType.name}</span>?</p>
                </div>
                <div className="flex justify-end gap-2">
                    <Can permission="expense-types.delete">
                        <Button variant="destructive" onClick={destroy}><Trash2 className="size-4" /> Delete</Button>
                    </Can>
                </div>
            </div>
        </>
    );
}
DeleteExpenseType.layout = { breadcrumbs: [{ title: 'Delete Expense Type', href: '#' }] };
