import { Head, Link, router, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime12 } from '@/lib/datetime';

type Student = {
    id: number;
    name: string | null;
    phone: string | null;
    attended: boolean;
};
type Session = {
    id: number;
    type: 'subject' | 'rental' | 'external';
    title: string | null;
    income: number;
    attendance_count: number | null;
    starts_at: string;
    ends_at: string;
    outcome_recorded_at: string | null;
    canceled_at: string | null;
    is_past: boolean;
    teacher: { name: string } | null;
    subject: { name: string } | null;
    room: { name: string } | null;
    students: Student[];
};

const selectClass =
    'border-input text-foreground h-10 w-full rounded-md border bg-background px-3 text-sm';

function dateLabel(value: string): string {
    return formatDateTime12(value);
}

function money(value: number): string {
    return `${value.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ج.م`;
}

export default function SessionShow({
    session,
    availableStudents,
}: {
    session: Session;
    availableStudents: Student[];
}) {
    const isRental = session.type === 'rental';
    const isExternal = session.type === 'external';
    const showStudents = session.type === 'subject';

    const attendance = useForm<{
        income: string;
        attendance_count: string;
    }>({
        income: String(session.income ?? 0),
        attendance_count:
            session.attendance_count === null
                ? ''
                : String(session.attendance_count),
    });

    const addStudent = useForm<{
        student_id: number | '';
        name: string;
        phone: string;
    }>({ student_id: '', name: '', phone: '' });

    const isNewStudent = addStudent.data.student_id === '';

    const submitAttendance = (e: React.FormEvent) => {
        e.preventDefault();
        attendance.patch(`/admin/sessions/${session.id}`);
    };

    const submitAddStudent = (e: React.FormEvent) => {
        e.preventDefault();
        addStudent.post(`/admin/sessions/${session.id}/students`, {
            onSuccess: () => addStudent.reset(),
        });
    };

    const removeStudent = (id: number) => {
        router.delete(`/admin/sessions/${session.id}/students/${id}`);
    };

    const cancelSession = () => {
        router.post(`/admin/sessions/${session.id}/cancel`);
    };

    const restoreSession = () => {
        router.post(`/admin/sessions/${session.id}/restore`);
    };

    const sessionTitle = isRental || isExternal
        ? (session.title ?? (isExternal ? 'محاضرة خارجية' : 'حجز قاعة'))
        : `${session.teacher?.name ?? ''} — ${session.subject?.name ?? ''}`;

    return (
        <>
            <Head title="تفاصيل الحصة" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="تفاصيل الحصة"
                    description={sessionTitle}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/sessions">رجوع</Link>
                        </Button>
                    }
                />

                <div className="grid gap-3 rounded-xl border bg-card p-5 text-sm shadow-sm sm:grid-cols-2">
                    <div>
                        <span className="text-muted-foreground">النوع: </span>
                        {isRental
                            ? 'حجز قاعة خارجي'
                            : isExternal
                              ? 'محاضر خارجي'
                              : 'حصة دراسية'}
                    </div>
                    <div>
                        <span className="text-muted-foreground">القاعة: </span>
                        {session.room?.name ?? '—'}
                    </div>
                    {isRental || isExternal ? (
                        <div>
                            <span className="text-muted-foreground">
                                العنوان:{' '}
                            </span>
                            {session.title ?? '—'}
                        </div>
                    ) : (
                        <>
                            <div>
                                <span className="text-muted-foreground">
                                    المعلم:{' '}
                                </span>
                                {session.teacher?.name ?? '—'}
                            </div>
                            <div>
                                <span className="text-muted-foreground">
                                    المادة:{' '}
                                </span>
                                {session.subject?.name ?? '—'}
                            </div>
                        </>
                    )}
                    <div>
                        <span className="text-muted-foreground">البداية: </span>
                        {dateLabel(session.starts_at)}
                    </div>
                    <div>
                        <span className="text-muted-foreground">النهاية: </span>
                        {dateLabel(session.ends_at)}
                    </div>
                    {session.canceled_at && (
                        <div>
                            <span className="text-muted-foreground">
                                حالة الحصة:{' '}
                            </span>
                            ملغاة
                        </div>
                    )}
                    {session.outcome_recorded_at && (
                        <div>
                            <span className="text-muted-foreground">
                                آخر تسجيل:{' '}
                            </span>
                            {dateLabel(session.outcome_recorded_at)}
                        </div>
                    )}
                </div>

                <form
                    onSubmit={submitAttendance}
                    className="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <h2 className="text-base font-semibold text-primary">
                        الإيراد الفعلي والحضور
                    </h2>

                    {!session.is_past && (
                        <p className="text-muted-foreground rounded-md border border-dashed p-3 text-sm">
                            يمكن تسجيل الإيراد بعد بدء الحصة.
                        </p>
                    )}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="income">الإيراد الفعلي (ج.م)</Label>
                            <Input
                                id="income"
                                type="number"
                                min="0"
                                step="0.01"
                                value={attendance.data.income}
                                disabled={!session.is_past || !!session.canceled_at}
                                onChange={(e) =>
                                    attendance.setData('income', e.target.value)
                                }
                            />
                            <InputError message={attendance.errors.income} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="attendance_count">
                                عدد الحضور (اختياري)
                            </Label>
                            <Input
                                id="attendance_count"
                                type="number"
                                min="0"
                                disabled={!session.is_past || !!session.canceled_at}
                                value={attendance.data.attendance_count}
                                onChange={(e) =>
                                    attendance.setData(
                                        'attendance_count',
                                        e.target.value,
                                    )
                                }
                                placeholder="اتركه فارغًا إن لم يُعرف"
                            />
                            <InputError
                                message={attendance.errors.attendance_count}
                            />
                        </div>
                    </div>

                    {showStudents && session.students.length > 0 && (
                        <div className="rounded-lg border">
                            <div className="border-b bg-muted/40 p-3 text-sm font-medium">
                                طلاب الحصة ({session.students.length})
                            </div>
                            <ul className="divide-y">
                                {session.students.map((student) => (
                                    <li
                                        key={student.id}
                                        className="flex items-center justify-between gap-3 p-3"
                                    >
                                        <span>
                                            <span className="font-medium">
                                                {student.name ?? '—'}
                                            </span>
                                            {student.phone && (
                                                <span className="text-muted-foreground dir-ltr ms-2 text-xs">
                                                    {student.phone}
                                                </span>
                                            )}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                removeStudent(student.id)
                                            }
                                        >
                                            إزالة
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <div className="flex flex-wrap gap-2">
                        <Button
                            disabled={
                                attendance.processing ||
                                !session.is_past ||
                                !!session.canceled_at
                            }
                        >
                            حفظ
                        </Button>
                        {session.canceled_at ? (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={restoreSession}
                            >
                                استعادة الحصة
                            </Button>
                        ) : (
                            !session.outcome_recorded_at && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={cancelSession}
                                >
                                    إلغاء الحصة
                                </Button>
                            )
                        )}
                    </div>
                </form>

                {showStudents && (
                    <form
                        onSubmit={submitAddStudent}
                        className="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                    >
                        <h2 className="text-base font-semibold text-primary">
                            إضافة طالب للحصة
                        </h2>
                        <div className="grid gap-2">
                            <Label>طالب موجود</Label>
                            <select
                                className={selectClass}
                                value={addStudent.data.student_id}
                                onChange={(e) =>
                                    addStudent.setData(
                                        'student_id',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                            >
                                <option value="">طالب جديد</option>
                                {availableStudents.map((student) => (
                                    <option key={student.id} value={student.id}>
                                        {student.name ?? 'بدون اسم'}
                                        {student.phone
                                            ? ` — ${student.phone}`
                                            : ''}
                                    </option>
                                ))}
                            </select>
                            <InputError message={addStudent.errors.student_id} />
                        </div>

                        {isNewStudent && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">الاسم</Label>
                                    <Input
                                        id="name"
                                        value={addStudent.data.name}
                                        onChange={(e) =>
                                            addStudent.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="name"
                                    />
                                    <InputError message={addStudent.errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">رقم الهاتف</Label>
                                    <Input
                                        id="phone"
                                        value={addStudent.data.phone}
                                        onChange={(e) =>
                                            addStudent.setData(
                                                'phone',
                                                e.target.value,
                                            )
                                        }
                                        type="tel"
                                        inputMode="tel"
                                        className="dir-ltr text-start"
                                        autoComplete="tel"
                                    />
                                    <InputError
                                        message={addStudent.errors.phone}
                                    />
                                </div>
                                <p className="text-muted-foreground text-xs sm:col-span-2">
                                    أدخل الاسم أو رقم الهاتف على الأقل.
                                </p>
                            </div>
                        )}

                        <Button disabled={addStudent.processing}>
                            إضافة الطالب
                        </Button>
                    </form>
                )}
            </div>
        </>
    );
}
