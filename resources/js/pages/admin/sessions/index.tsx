import { Form, Head, Link, useForm } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination, type Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type SubjectOption = { id: number; name: string };
type TeacherOption = { id: number; name: string; subjects: SubjectOption[] };
type Session = {
    id: number;
    starts_at: string;
    ends_at: string;
    students_count: number;
    teacher: { name: string };
    subject: { name: string };
};

const selectClass =
    'border-input text-foreground h-10 w-full rounded-md border bg-background px-3 text-sm';

function subjectsForTeacher(
    teachers: TeacherOption[],
    teacherId: number | '',
): SubjectOption[] {
    return teachers.find((t) => t.id === teacherId)?.subjects ?? [];
}

export default function SessionsIndex({
    sessions,
    teachers,
}: {
    sessions: Paginated<Session>;
    teachers: TeacherOption[];
}) {
    const addForm = useForm<{
        teacher_id: number | '';
        subject_id: number | '';
        starts_at: string;
        ends_at: string;
    }>({ teacher_id: '', subject_id: '', starts_at: '', ends_at: '' });

    const submitAdd = (e: React.FormEvent) => {
        e.preventDefault();
        addForm.post('/admin/sessions');
    };

    return (
        <>
            <Head title="الحصص الاستثنائية" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="الحصص الاستثنائية"
                    description="أضف حصة استثنائية لمعلم ثم أضف الطلاب إليها."
                />

                <form
                    onSubmit={submitAdd}
                    className="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <h2 className="text-base font-semibold text-primary">
                        إضافة حصة استثنائية
                    </h2>
                    <div className="grid gap-4 md:grid-cols-2">
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
                                    <option key={teacher.id} value={teacher.id}>
                                        {teacher.name}
                                    </option>
                                ))}
                            </select>
                            <InputErrorText message={addForm.errors.teacher_id} />
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
                                    <option key={subject.id} value={subject.id}>
                                        {subject.name}
                                    </option>
                                ))}
                            </select>
                            <InputErrorText message={addForm.errors.subject_id} />
                        </div>
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
                        title="لا توجد حصص استثنائية بعد."
                        description="أضف أول حصة استثنائية من الأعلى."
                    />
                ) : (
                    <>
                        <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/60 text-start">
                                    <tr>
                                        <th className="p-3 font-medium">
                                            المعلم
                                        </th>
                                        <th className="p-3 font-medium">
                                            المادة
                                        </th>
                                        <th className="p-3 font-medium">
                                            الموعد
                                        </th>
                                        <th className="p-3 font-medium">
                                            الطلاب
                                        </th>
                                        <th className="p-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {sessions.data.map((session) => (
                                        <tr
                                            key={session.id}
                                            className="border-t transition-colors hover:bg-muted/30"
                                        >
                                            <td className="p-3 font-medium">
                                                {session.teacher.name}
                                            </td>
                                            <td className="p-3">
                                                {session.subject.name}
                                            </td>
                                            <td className="p-3">
                                                {new Date(
                                                    session.starts_at,
                                                ).toLocaleString('ar-EG', {
                                                    dateStyle: 'medium',
                                                    timeStyle: 'short',
                                                })}
                                            </td>
                                            <td className="p-3">
                                                {session.students_count}
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
                                                            التفاصيل والطلاب
                                                        </Link>
                                                    </Button>
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
