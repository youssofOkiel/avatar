import { Form, Head, Link, useForm } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';

type Student = { id: number; name: string | null; phone: string | null };
type Session = {
    id: number;
    starts_at: string;
    ends_at: string;
    teacher: { name: string };
    subject: { name: string };
    students: Student[];
};

const selectClass =
    'border-input text-foreground h-10 w-full rounded-md border bg-background px-3 text-sm';

export default function SessionShow({
    session,
    availableStudents,
}: {
    session: Session;
    availableStudents: Student[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        student_id: number | '';
        name: string;
        phone: string;
    }>({ student_id: '', name: '', phone: '' });

    const isNewStudent = data.student_id === '';

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/admin/sessions/${session.id}/students`, {
            onSuccess: () => reset(),
        });
    };

    const dateLabel = (value: string) =>
        new Date(value).toLocaleString('ar-EG', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });

    return (
        <>
            <Head title="تفاصيل الحصة" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="تفاصيل الحصة الاستثنائية"
                    description={`${session.teacher.name} — ${session.subject.name}`}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/sessions">رجوع</Link>
                        </Button>
                    }
                />

                <div className="grid gap-3 rounded-xl border bg-card p-5 text-sm shadow-sm sm:grid-cols-2">
                    <div>
                        <span className="text-muted-foreground">المعلم: </span>
                        {session.teacher.name}
                    </div>
                    <div>
                        <span className="text-muted-foreground">المادة: </span>
                        {session.subject.name}
                    </div>
                    <div>
                        <span className="text-muted-foreground">البداية: </span>
                        {dateLabel(session.starts_at)}
                    </div>
                    <div>
                        <span className="text-muted-foreground">النهاية: </span>
                        {dateLabel(session.ends_at)}
                    </div>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <h2 className="text-base font-semibold text-primary">
                        إضافة طالب للحصة
                    </h2>
                    <div className="grid gap-2">
                        <Label>طالب موجود</Label>
                        <select
                            className={selectClass}
                            value={data.student_id}
                            onChange={(e) =>
                                setData(
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
                                    {student.phone ? ` — ${student.phone}` : ''}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.student_id} />
                    </div>

                    {isNewStudent && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">الاسم</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    autoComplete="name"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">رقم الهاتف</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                    type="tel"
                                    inputMode="tel"
                                    className="dir-ltr text-start"
                                    autoComplete="tel"
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <p className="text-muted-foreground text-xs sm:col-span-2">
                                أدخل الاسم أو رقم الهاتف على الأقل.
                            </p>
                        </div>
                    )}

                    <Button disabled={processing}>إضافة الطالب</Button>
                </form>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="border-b bg-muted/40 p-3 text-base font-semibold text-primary">
                        طلاب الحصة ({session.students.length})
                    </div>
                    {session.students.length === 0 ? (
                        <p className="text-muted-foreground p-5 text-sm">
                            لم تتم إضافة طلاب لهذه الحصة بعد.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="bg-muted/60 text-start">
                                <tr>
                                    <th className="p-3 font-medium">الاسم</th>
                                    <th className="p-3 font-medium">
                                        رقم الهاتف
                                    </th>
                                    <th className="p-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {session.students.map((student) => (
                                    <tr
                                        key={student.id}
                                        className="border-t transition-colors hover:bg-muted/30"
                                    >
                                        <td className="p-3 font-medium">
                                            {student.name ?? '—'}
                                        </td>
                                        <td className="p-3 dir-ltr text-start">
                                            {student.phone ?? '—'}
                                        </td>
                                        <td className="p-3 text-end">
                                            <Form
                                                action={`/admin/sessions/${session.id}/students/${student.id}`}
                                                method="delete"
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={processing}
                                                    >
                                                        إزالة
                                                    </Button>
                                                )}
                                            </Form>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </>
    );
}
