import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { dayLabel, formatTimeRange12 } from '@/lib/datetime';

type Slot = {
    topic: string;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    room: string | null;
    income: number;
};

type Lecturer = {
    id: number;
    name: string;
};

function money(value: number): string {
    return `${value.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ج.م`;
}

export default function ExternalLecturerShow({
    lecturer,
    schedules,
}: {
    lecturer: Lecturer;
    schedules: Slot[];
}) {
    return (
        <>
            <Head title={lecturer.name} />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={lecturer.name}
                    description="المواعيد الأسبوعية للمحاضر الخارجي."
                    actions={
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link
                                    href={`/admin/external-lecturers/${lecturer.id}/edit`}
                                >
                                    تعديل
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/admin/external-lecturers">
                                    رجوع
                                </Link>
                            </Button>
                        </div>
                    }
                />

                {schedules.length === 0 ? (
                    <div className="rounded-xl border bg-card p-5 text-sm text-muted-foreground shadow-sm">
                        لا توجد مواعيد مسجّلة لهذا المحاضر بعد.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/60 text-start">
                                <tr>
                                    <th className="p-3 font-medium">الموضوع</th>
                                    <th className="p-3 font-medium">اليوم</th>
                                    <th className="p-3 font-medium">الوقت</th>
                                    <th className="p-3 font-medium">القاعة</th>
                                    <th className="p-3 font-medium">الإيراد</th>
                                </tr>
                            </thead>
                            <tbody>
                                {schedules.map((slot, i) => (
                                    <tr
                                        key={i}
                                        className="border-t transition-colors hover:bg-muted/30"
                                    >
                                        <td className="p-3 font-medium">
                                            {slot.topic}
                                        </td>
                                        <td className="p-3">
                                            {dayLabel(slot.day_of_week)}
                                        </td>
                                        <td className="p-3">
                                            {formatTimeRange12(
                                                slot.starts_at,
                                                slot.ends_at,
                                            )}
                                        </td>
                                        <td className="p-3">
                                            {slot.room ?? '—'}
                                        </td>
                                        <td className="p-3">
                                            {money(slot.income)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
