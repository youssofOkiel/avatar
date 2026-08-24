import { Form, Head, Link, router, useForm } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination  } from '@/components/pagination';
import type {Paginated} from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime12 } from '@/lib/datetime';

type SubjectOption = { id: number; name: string };
type TeacherOption = { id: number; name: string; subjects: SubjectOption[] };
type RoomOption = { id: number; name: string };
type Session = {
    id: number;
    type: 'subject' | 'rental' | 'external';
    title: string | null;
    starts_at: string;
    income: number;
    attendance: number | null;
    outcome_recorded_at: string | null;
    canceled_at: string | null;
    is_past: boolean;
    teacher: { name: string } | null;
    subject: { name: string } | null;
    room: { name: string } | null;
};

const selectClass =
    'border-input text-foreground h-10 w-full rounded-md border bg-background px-3 text-sm';

function subjectsForTeacher(
    teachers: TeacherOption[],
    teacherId: number | '',
): SubjectOption[] {
    return teachers.find((t) => t.id === teacherId)?.subjects ?? [];
}

function money(value: number): string {
    return `${value.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ج.م`;
}

function cancelSession(id: number): void {
    router.post(`/admin/sessions/${id}/cancel`);
}

function restoreSession(id: number): void {
    router.post(`/admin/sessions/${id}/restore`);
}

export default function SessionsIndex({
    sessions,
    teachers,
    rooms,
    filter,
}: {
    sessions: Paginated<Session>;
    teachers: TeacherOption[];
    rooms: RoomOption[];
    filter: 'all' | 'pending';
}) {
    const addForm = useForm<{
        type: 'subject' | 'rental';
        teacher_id: number | '';
        subject_id: number | '';
        title: string;
        room_id: number | '';
        attendance_count: string;
        starts_at: string;
        ends_at: string;
    }>({
        type: 'subject',
        teacher_id: '',
        subject_id: '',
        title: '',
        room_id: '',
        attendance_count: '',
        starts_at: '',
        ends_at: '',
    });

    const isRental = addForm.data.type === 'rental';

    const submitAdd = (e: React.FormEvent) => {
        e.preventDefault();
        addForm.post('/admin/sessions');
    };

    return (
        <>
            <Head title="الحصص" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="الحصص"
                    description="أضف حصة أو حجز قاعة، ثم سجّل الإيراد الفعلي بعد انتهائها."
                />

                <div className="flex gap-2">
                    <Button
                        variant={filter === 'all' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() =>
                            router.get('/admin/sessions', {}, { preserveState: true })
                        }
                    >
                        كل الحصص
                    </Button>
                    <Button
                        variant={filter === 'pending' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() =>
                            router.get(
                                '/admin/sessions',
                                { filter: 'pending' },
                                { preserveState: true },
                            )
                        }
                    >
                        بانتظار تسجيل الإيراد
                    </Button>
                </div>

                <form
                    onSubmit={submitAdd}
                    className="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <h2 className="text-base font-semibold text-primary">
                        إضافة حصة
                    </h2>

                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={() => addForm.setData('type', 'subject')}
                            className={`rounded-md border px-4 py-2 text-sm ${
                                !isRental
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'text-muted-foreground'
                            }`}
                        >
                            حصة دراسية
                        </button>
                        <button
                            type="button"
                            onClick={() => addForm.setData('type', 'rental')}
                            className={`rounded-md border px-4 py-2 text-sm ${
                                isRental
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'text-muted-foreground'
                            }`}
                        >
                            حجز قاعة خارجي
                        </button>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        {isRental ? (
                            <div className="grid gap-2 md:col-span-2">
                                <Label>عنوان الحجز</Label>
                                <Input
                                    value={addForm.data.title}
                                    onChange={(e) =>
                                        addForm.setData('title', e.target.value)
                                    }
                                    placeholder="مثال: محاضرة خارجية، اجتماع..."
                                    required
                                />
                                <InputErrorText message={addForm.errors.title} />
                            </div>
                        ) : (
                            <>
                                <div className="grid gap-2">
                                    <Label>المعلم</Label>
                                    <select
                                        className={selectClass}
                                        value={addForm.data.teacher_id}
                                        onChange={(e) => {
                                            addForm.setData(
                                                'teacher_id',
                                                Number(e.target.value),
                                            );
                                            addForm.setData('subject_id', '');
                                        }}
                                        required
                                    >
                                        <option value="" disabled>
                                            اختر المعلم
                                        </option>
                                        {teachers.map((teacher) => (
                                            <option
                                                key={teacher.id}
                                                value={teacher.id}
                                            >
                                                {teacher.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputErrorText
                                        message={addForm.errors.teacher_id}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>المادة</Label>
                                    <select
                                        className={selectClass}
                                        value={addForm.data.subject_id}
                                        onChange={(e) =>
                                            addForm.setData(
                                                'subject_id',
                                                Number(e.target.value),
                                            )
                                        }
                                        required
                                    >
                                        <option value="" disabled>
                                            اختر المادة
                                        </option>
                                        {subjectsForTeacher(
                                            teachers,
                                            addForm.data.teacher_id,
                                        ).map((subject) => (
                                            <option
                                                key={subject.id}
                                                value={subject.id}
                                            >
                                                {subject.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputErrorText
                                        message={addForm.errors.subject_id}
                                    />
                                </div>
                            </>
                        )}

                        <div className="grid gap-2">
                            <Label>القاعة</Label>
                            <select
                                className={selectClass}
                                value={addForm.data.room_id}
                                onChange={(e) =>
                                    addForm.setData(
                                        'room_id',
                                        Number(e.target.value),
                                    )
                                }
                                required
                            >
                                <option value="" disabled>
                                    اختر القاعة
                                </option>
                                {rooms.map((room) => (
                                    <option key={room.id} value={room.id}>
                                        {room.name}
                                    </option>
                                ))}
                            </select>
                            <InputErrorText message={addForm.errors.room_id} />
                        </div>

                        {isRental && (
                            <div className="grid gap-2">
                                <Label>عدد الحضور</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={addForm.data.attendance_count}
                                    onChange={(e) =>
                                        addForm.setData(
                                            'attendance_count',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="0"
                                />
                                <InputErrorText
                                    message={addForm.errors.attendance_count}
                                />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label>البداية</Label>
                            <Input
                                type="datetime-local"
                                value={addForm.data.starts_at}
                                onChange={(e) =>
                                    addForm.setData('starts_at', e.target.value)
                                }
                                required
                            />
                            <InputErrorText message={addForm.errors.starts_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label>النهاية</Label>
                            <Input
                                type="datetime-local"
                                value={addForm.data.ends_at}
                                onChange={(e) =>
                                    addForm.setData('ends_at', e.target.value)
                                }
                                required
                            />
                            <InputErrorText message={addForm.errors.ends_at} />
                        </div>
                    </div>
                    <Button disabled={addForm.processing}>إضافة الحصة</Button>
                </form>

                {sessions.data.length === 0 ? (
                    <EmptyState
                        title="لا توجد حصص بعد."
                        description="أضف أول حصة من الأعلى."
                    />
                ) : (
                    <>
                        <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/60 text-start">
                                    <tr>
                                        <th className="p-3 font-medium">النوع</th>
                                        <th className="p-3 font-medium">
                                            المادة / العنوان
                                        </th>
                                        <th className="p-3 font-medium">القاعة</th>
                                        <th className="p-3 font-medium">الموعد</th>
                                        <th className="p-3 font-medium">
                                            الإيراد الفعلي
                                        </th>
                                        <th className="p-3 font-medium">الحالة</th>
                                        <th className="p-3 font-medium">الحضور</th>
                                        <th className="p-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {sessions.data.map((session) => (
                                        <tr
                                            key={session.id}
                                            className="border-t transition-colors hover:bg-muted/30"
                                        >
                                            <td className="p-3">
                                                {session.type === 'rental'
                                                    ? 'حجز قاعة'
                                                    : session.type === 'external'
                                                      ? 'محاضر خارجي'
                                                      : 'حصة دراسية'}
                                            </td>
                                            <td className="p-3 font-medium">
                                                {session.type === 'rental' ||
                                                session.type === 'external'
                                                    ? session.title
                                                    : `${session.subject?.name ?? '-'} — ${session.teacher?.name ?? '-'}`}
                                            </td>
                                            <td className="p-3">
                                                {session.room?.name ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {formatDateTime12(
                                                    session.starts_at,
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {session.outcome_recorded_at
                                                    ? money(session.income)
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                {!session.is_past
                                                    ? 'قادمة'
                                                    : session.canceled_at
                                                      ? 'ملغاة'
                                                      : session.outcome_recorded_at
                                                        ? 'مسجّل'
                                                        : 'بانتظار التسجيل'}
                                            </td>
                                            <td className="p-3">
                                                {session.attendance ?? '—'}
                                            </td>
                                            <td className="p-3 text-end">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/sessions/${session.id}`}
                                                        >
                                                            التفاصيل
                                                        </Link>
                                                    </Button>
                                                    {session.canceled_at ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                restoreSession(
                                                                    session.id,
                                                                )
                                                            }
                                                        >
                                                            استعادة
                                                        </Button>
                                                    ) : (
                                                        !session.outcome_recorded_at && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    cancelSession(
                                                                        session.id,
                                                                    )
                                                                }
                                                            >
                                                                إلغاء
                                                            </Button>
                                                        )
                                                    )}
                                                    <Form
                                                        action={`/admin/sessions/${session.id}`}
                                                        method="delete"
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                حذف
                                                            </Button>
                                                        )}
                                                    </Form>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination meta={sessions} />
                    </>
                )}
            </div>
        </>
    );
}

function InputErrorText({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="text-sm text-destructive">{message}</p>;
}
