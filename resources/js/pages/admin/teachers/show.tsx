import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { dayLabel, formatTimeRange12 } from '@/lib/datetime';

type Slot = {
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    room?: string | null;
};
type ReservationRow = {
    id: number;
    student_name: string | null;
    student_phone: string | null;
    slots: Slot[];
};
type Teaching = {
    education_level: { id: number; name: string };
    subject: { id: number; name: string };
    schedules: Slot[];
    reservations: ReservationRow[];
};
type Teacher = {
    id: number;
    name: string;
    bio: string | null;
    is_active: boolean;
    reservations_count: number;
};

function slotLabel(slot: Slot): string {
    return `${dayLabel(slot.day_of_week)} ${formatTimeRange12(slot.starts_at, slot.ends_at)}`;
}

export default function TeacherShow({
    teacher,
    teaching,
}: {
    teacher: Teacher;
    teaching: Teaching[];
}) {
    return (
        <>
            <Head title={teacher.name} />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={teacher.name}
                    description={`إجمالي الحجوزات: ${teacher.reservations_count}`}
                    actions={
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link href={`/admin/teachers/${teacher.id}/edit`}>
                                    تعديل
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/admin/teachers">رجوع</Link>
                            </Button>
                        </div>
                    }
                />

                {teacher.bio && (
                    <div className="rounded-xl border bg-card p-5 text-sm shadow-sm">
                        <span className="text-muted-foreground">نبذة: </span>
                        {teacher.bio}
                    </div>
                )}

                {teaching.length === 0 ? (
                    <div className="rounded-xl border bg-card p-5 text-sm text-muted-foreground shadow-sm">
                        لم يتم تعيين مواد لهذا المعلم بعد.
                    </div>
                ) : (
                    teaching.map((item) => (
                        <div
                            key={`${item.education_level.id}-${item.subject.id}`}
                            className="overflow-hidden rounded-xl border bg-card shadow-sm"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/40 p-4">
                                <h2 className="text-base font-semibold text-primary">
                                    {item.subject.name}
                                    <span className="text-muted-foreground text-sm font-normal">
                                        {' '}
                                        — {item.education_level.name}
                                    </span>
                                </h2>
                                <span className="text-muted-foreground text-sm">
                                    الحجوزات: {item.reservations.length}
                                </span>
                            </div>

                            <div className="grid gap-4 p-4 md:grid-cols-2">
                                <div>
                                    <h3 className="mb-2 text-sm font-medium">
                                        المواعيد الأسبوعية
                                    </h3>
                                    {item.schedules.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">
                                            لا توجد مواعيد مسجّلة.
                                        </p>
                                    ) : (
                                        <ul className="flex flex-wrap gap-2">
                                            {item.schedules.map((slot, i) => (
                                                <li
                                                    key={i}
                                                    className="rounded-full bg-secondary px-3 py-1 text-xs text-secondary-foreground"
                                                >
                                                    {slotLabel(slot)}
                                                    {slot.room
                                                        ? ` · ${slot.room}`
                                                        : ''}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>

                                <div>
                                    <h3 className="mb-2 text-sm font-medium">
                                        الطلاب المحجوزون
                                    </h3>
                                    {item.reservations.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">
                                            لا توجد حجوزات على هذه المادة.
                                        </p>
                                    ) : (
                                        <ul className="flex flex-col gap-2">
                                            {item.reservations.map(
                                                (reservation) => (
                                                    <li
                                                        key={reservation.id}
                                                        className="rounded-md border bg-background p-2 text-sm"
                                                    >
                                                        <div className="font-medium">
                                                            {reservation.student_name ??
                                                                '—'}
                                                            {reservation.student_phone && (
                                                                <span className="text-muted-foreground dir-ltr mr-2 text-xs">
                                                                    {
                                                                        reservation.student_phone
                                                                    }
                                                                </span>
                                                            )}
                                                        </div>
                                                        {reservation.slots
                                                            .length > 0 && (
                                                            <div className="text-muted-foreground mt-1 text-xs">
                                                                {reservation.slots
                                                                    .map(
                                                                        slotLabel,
                                                                    )
                                                                    .join('، ')}
                                                            </div>
                                                        )}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </>
    );
}
