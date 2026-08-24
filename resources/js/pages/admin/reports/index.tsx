import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Row = {
    date: string;
    sessions: number;
    attendance: number;
    income: number;
    expenses: number;
    net: number;
};

type Totals = {
    sessions: number;
    attendance: number;
    income: number;
    expenses: number;
    net: number;
};

function money(value: number): string {
    return `${value.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ج.م`;
}

function dateLabel(value: string): string {
    return new Date(value).toLocaleDateString('ar-EG', { dateStyle: 'medium' });
}

export default function ReportsIndex({
    rows,
    totals,
    filters,
}: {
    rows: Row[];
    totals: Totals;
    filters: { from: string; to: string };
}) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const applyFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/reports', { from, to }, { preserveState: true });
    };

    return (
        <>
            <Head title="التقارير المالية" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="التقارير المالية"
                    description="ملخص يومي للحصص والحضور والإيرادات والمصروفات والصافي."
                />

                <form
                    onSubmit={applyFilter}
                    className="flex flex-wrap items-end gap-4 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="from">من</Label>
                        <Input
                            id="from"
                            type="date"
                            value={from}
                            onChange={(e) => setFrom(e.target.value)}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="to">إلى</Label>
                        <Input
                            id="to"
                            type="date"
                            value={to}
                            onChange={(e) => setTo(e.target.value)}
                        />
                    </div>
                    <Button type="submit">تطبيق</Button>
                </form>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <SummaryCard label="عدد الحصص" value={totals.sessions} />
                    <SummaryCard label="إجمالي الحضور" value={totals.attendance} />
                    <SummaryCard label="الإيرادات" value={money(totals.income)} />
                    <SummaryCard label="الصافي" value={money(totals.net)} />
                </div>

                {rows.length === 0 ? (
                    <EmptyState
                        title="لا توجد بيانات في هذه الفترة."
                        description="جرّب تغيير نطاق التاريخ."
                    />
                ) : (
                    <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/60 text-start">
                                <tr>
                                    <th className="p-3 font-medium">التاريخ</th>
                                    <th className="p-3 font-medium">الحصص</th>
                                    <th className="p-3 font-medium">الحضور</th>
                                    <th className="p-3 font-medium">الإيرادات</th>
                                    <th className="p-3 font-medium">المصروفات</th>
                                    <th className="p-3 font-medium">الصافي</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr
                                        key={row.date}
                                        className="border-t transition-colors hover:bg-muted/30"
                                    >
                                        <td className="p-3 font-medium">
                                            {dateLabel(row.date)}
                                        </td>
                                        <td className="p-3">{row.sessions}</td>
                                        <td className="p-3">{row.attendance}</td>
                                        <td className="p-3">
                                            {money(row.income)}
                                        </td>
                                        <td className="p-3">
                                            {money(row.expenses)}
                                        </td>
                                        <td
                                            className={`p-3 font-medium ${row.net < 0 ? 'text-destructive' : ''}`}
                                        >
                                            {money(row.net)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="border-t bg-muted/40 font-semibold">
                                    <td className="p-3">الإجمالي</td>
                                    <td className="p-3">{totals.sessions}</td>
                                    <td className="p-3">{totals.attendance}</td>
                                    <td className="p-3">
                                        {money(totals.income)}
                                    </td>
                                    <td className="p-3">
                                        {money(totals.expenses)}
                                    </td>
                                    <td className="p-3">{money(totals.net)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

function SummaryCard({
    label,
    value,
}: {
    label: string;
    value: string | number;
}) {
    return (
        <div className="rounded-xl border bg-card p-5 shadow-sm">
            <div className="text-muted-foreground text-sm">{label}</div>
            <div className="mt-2 text-2xl font-bold">{value}</div>
        </div>
    );
}
