import { Form, Head, Link } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination, type Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { dayLabel, formatTime12 } from '@/lib/datetime';

type ScheduleSlot = {
    id: number;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    room?: string | null;
};

type Reservation = {
    id: number;
    student: { name: string | null; phone: string | null };
    subject: { name: string };
    teacher: { name: string } | null;
    education_level: { name: string } | null;
    teacher_schedules: ScheduleSlot[];
};

function formatSlot(slot: ScheduleSlot): string {
    const base = `${dayLabel(slot.day_of_week)} ${formatTime12(slot.starts_at)}`;

    return slot.room ? `${base} · ${slot.room}` : base;
}

export default function AdminReservationsIndex({
    reservations,
}: {
    reservations: Paginated<Reservation>;
}) {
    return (
        <>
            <Head title="حجوزات الطلاب" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="حجوزات الطلاب"
                    description="سجّل حجوزات الطلاب في المواد المختلفة."
                    actions={
                        <Button asChild>
                            <Link href="/admin/reservations/create">
                                حجز جديد
                            </Link>
                        </Button>
                    }
                />

                {reservations.data.length === 0 ? (
                    <EmptyState
                        title="لا توجد حجوزات بعد."
                        description="ابدأ بتسجيل أول حجز."
                        action={
                            <Button asChild>
                                <Link href="/admin/reservations/create">
                                    حجز جديد
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/60 text-start">
                                    <tr>
                                        <th className="p-3 font-medium">
                                            الطالب
                                        </th>
                                        <th className="p-3 font-medium">
                                            رقم الهاتف
                                        </th>
                                        <th className="p-3 font-medium">
                                            المرحلة
                                        </th>
                                        <th className="p-3 font-medium">
                                            المادة
                                        </th>
                                        <th className="p-3 font-medium">
                                            المعلم
                                        </th>
                                        <th className="p-3 font-medium">
                                            المواعيد
                                        </th>
                                        <th className="p-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {reservations.data.map((reservation) => (
                                        <tr
                                            key={reservation.id}
                                            className="border-t transition-colors hover:bg-muted/30"
                                        >
                                            <td className="p-3 font-medium">
                                                {reservation.student.name ?? '—'}
                                            </td>
                                            <td className="p-3 dir-ltr text-start">
                                                {reservation.student.phone ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {reservation.education_level
                                                    ?.name ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {reservation.subject.name}
                                            </td>
                                            <td className="p-3">
                                                {reservation.teacher?.name ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {reservation.teacher_schedules
                                                    .length === 0
                                                    ? '—'
                                                    : reservation.teacher_schedules
                                                          .map(formatSlot)
                                                          .join('، ')}
                                            </td>
                                            <td className="p-3 text-end">
                                                <Form
                                                    action={`/admin/reservations/${reservation.id}`}
                                                    method="delete"
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="outline"
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
                        <Pagination meta={reservations} />
                    </>
                )}
            </div>
        </>
    );
}
