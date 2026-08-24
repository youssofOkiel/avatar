import { Form, Head, useForm } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Pagination  } from '@/components/pagination';
import type {Paginated} from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Expense = {
    id: number;
    date: string;
    amount: string;
    description: string;
};

function money(value: number): string {
    return `${value.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ج.م`;
}

export default function ExpensesIndex({
    expenses,
    total,
}: {
    expenses: Paginated<Expense>;
    total: number;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        date: string;
        amount: string;
        description: string;
    }>({
        date: new Date().toISOString().slice(0, 10),
        amount: '',
        description: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/expenses', {
            onSuccess: () => reset('amount', 'description'),
        });
    };

    return (
        <>
            <Head title="المصروفات" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="المصروفات"
                    description="سجّل المصروفات اليومية للمركز."
                />

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <h2 className="text-base font-semibold text-primary">
                        إضافة مصروف
                    </h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="date">التاريخ</Label>
                            <Input
                                id="date"
                                type="date"
                                value={data.date}
                                onChange={(e) => setData('date', e.target.value)}
                                required
                            />
                            <InputError message={errors.date} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="amount">المبلغ (ج.م)</Label>
                            <Input
                                id="amount"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.amount}
                                onChange={(e) =>
                                    setData('amount', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.amount} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="description">الوصف</Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.description} />
                        </div>
                    </div>
                    <Button disabled={processing}>إضافة</Button>
                </form>

                <div className="rounded-xl border bg-card p-4 text-sm shadow-sm">
                    <span className="text-muted-foreground">إجمالي المصروفات: </span>
                    <span className="font-semibold">{money(total)}</span>
                </div>

                {expenses.data.length === 0 ? (
                    <EmptyState
                        title="لا توجد مصروفات بعد."
                        description="أضف أول مصروف من الأعلى."
                    />
                ) : (
                    <>
                        <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/60 text-start">
                                    <tr>
                                        <th className="p-3 font-medium">
                                            التاريخ
                                        </th>
                                        <th className="p-3 font-medium">الوصف</th>
                                        <th className="p-3 font-medium">المبلغ</th>
                                        <th className="p-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {expenses.data.map((expense) => (
                                        <tr
                                            key={expense.id}
                                            className="border-t transition-colors hover:bg-muted/30"
                                        >
                                            <td className="p-3">
                                                {new Date(
                                                    expense.date,
                                                ).toLocaleDateString('ar-EG', {
                                                    dateStyle: 'medium',
                                                })}
                                            </td>
                                            <td className="p-3">
                                                {expense.description}
                                            </td>
                                            <td className="p-3 font-medium">
                                                {money(Number(expense.amount))}
                                            </td>
                                            <td className="p-3 text-end">
                                                <Form
                                                    action={`/admin/expenses/${expense.id}`}
                                                    method="delete"
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            disabled={processing}
                                                        >
                                                            حذف
                                                        </Button>
                                                    )}
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination meta={expenses} />
                    </>
                )}
            </div>
        </>
    );
}
