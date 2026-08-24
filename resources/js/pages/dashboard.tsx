import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';

type Stats = {
    teachers: number;
    students: number;
    reservations: number;
    sessions: number;
};

type Finance = {
    total_income: number;
    total_expenses: number;
    total_net: number;
    month_income: number;
    month_expenses: number;
    month_net: number;
};

function money(value: number): string {
    return `${value.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ج.م`;
}

function StatCard({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-xl border bg-card p-5 shadow-sm">
            <div className="text-muted-foreground text-sm">{label}</div>
            <div className="mt-2 text-2xl font-bold">{value}</div>
        </div>
    );
}

export default function Dashboard({
    stats,
    finance,
}: {
    stats: Stats;
    finance: Finance;
}) {
    return (
        <>
            <Head title="لوحة التحكم" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="لوحة التحكم"
                    description="نظرة عامة على المركز والحسابات."
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="المعلمون" value={stats.teachers} />
                    <StatCard label="الطلاب" value={stats.students} />
                    <StatCard label="الحجوزات" value={stats.reservations} />
                    <StatCard label="الحصص" value={stats.sessions} />
                </div>

                <div>
                    <h2 className="mb-3 text-base font-semibold text-primary">
                        الحسابات (الإجمالي)
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <StatCard
                            label="إجمالي الإيرادات"
                            value={money(finance.total_income)}
                        />
                        <StatCard
                            label="إجمالي المصروفات"
                            value={money(finance.total_expenses)}
                        />
                        <StatCard
                            label="الصافي"
                            value={money(finance.total_net)}
                        />
                    </div>
                </div>

                <div>
                    <h2 className="mb-3 text-base font-semibold text-primary">
                        حسابات الشهر الحالي
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <StatCard
                            label="إيرادات الشهر"
                            value={money(finance.month_income)}
                        />
                        <StatCard
                            label="مصروفات الشهر"
                            value={money(finance.month_expenses)}
                        />
                        <StatCard
                            label="صافي الشهر"
                            value={money(finance.month_net)}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
