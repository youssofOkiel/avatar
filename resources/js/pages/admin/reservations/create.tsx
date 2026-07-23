import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type SubjectOption = { id: number; name: string };
type Level = { id: number; name: string; subjects: SubjectOption[] };
type Student = { id: number; name: string | null; phone: string | null };
type TeacherSubject = {
    teacher_id: number;
    teacher_name: string;
    education_level_id: number;
    subject_id: number;
};
type Schedule = {
    id: number;
    teacher_id: number;
    education_level_id: number;
    subject_id: number;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
};

const DAYS = [
    'الأحد',
    'الإثنين',
    'الثلاثاء',
    'الأربعاء',
    'الخميس',
    'الجمعة',
    'السبت',
];

const selectClass =
    'border-input text-foreground h-10 w-full rounded-md border bg-background px-3 text-sm';

export default function AdminReservationsCreate({
    students,
    levels,
    teacherSubjects,
    schedules,
}: {
    students: Student[];
    levels: Level[];
    teacherSubjects: TeacherSubject[];
    schedules: Schedule[];
}) {
    const { data, setData, post, processing, errors } = useForm<{
        student_id: number | '';
        name: string;
        phone: string;
        education_level_id: number | '';
        subject_id: number | '';
        teacher_id: number | '';
        teacher_schedule_ids: number[];
    }>({
        student_id: '',
        name: '',
        phone: '',
        education_level_id: '',
        subject_id: '',
        teacher_id: '',
        teacher_schedule_ids: [],
    });

    const isNewStudent = data.student_id === '';

    const activeSubjects =
        levels.find((l) => l.id === data.education_level_id)?.subjects ?? [];

    const availableTeachers =
        data.education_level_id !== '' && data.subject_id !== ''
            ? teacherSubjects
                  .filter(
                      (ts) =>
                          ts.education_level_id === data.education_level_id &&
                          ts.subject_id === data.subject_id,
                  )
                  .map((ts) => ({ id: ts.teacher_id, name: ts.teacher_name }))
            : [];

    const availableSchedules =
        data.teacher_id !== ''
            ? schedules
                  .filter(
                      (s) =>
                          s.teacher_id === data.teacher_id &&
                          s.education_level_id === data.education_level_id &&
                          s.subject_id === data.subject_id,
                  )
                  .sort(
                      (a, b) =>
                          a.day_of_week - b.day_of_week ||
                          a.starts_at.localeCompare(b.starts_at),
                  )
            : [];

    const toggleSchedule = (id: number) => {
        setData(
            'teacher_schedule_ids',
            data.teacher_schedule_ids.includes(id)
                ? data.teacher_schedule_ids.filter((x) => x !== id)
                : [...data.teacher_schedule_ids, id],
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/reservations');
    };

    return (
        <>
            <Head title="حجز جديد" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="حجز جديد"
                    description="اختر الطالب، ثم المرحلة والمادة والمعلم والموعد."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/reservations">رجوع</Link>
                        </Button>
                    }
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-4 rounded-xl border bg-card p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-primary">
                            الطالب
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
                                {students.map((student) => (
                                    <option key={student.id} value={student.id}>
                                        {student.name ?? 'بدون اسم'}
                                        {student.phone
                                            ? ` — ${student.phone}`
                                            : ''}
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
                    </div>

                    <div className="space-y-4 rounded-xl border bg-card p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-primary">
                            المادة والمعلم
                        </h2>

                        <div className="grid gap-2">
                            <Label>المرحلة الدراسية</Label>
                            <select
                                className={selectClass}
                                value={data.education_level_id}
                                onChange={(e) => {
                                    setData((prev) => ({
                                        ...prev,
                                        education_level_id:
                                            e.target.value === ''
                                                ? ''
                                                : Number(e.target.value),
                                        subject_id: '',
                                        teacher_id: '',
                                        teacher_schedule_ids: [],
                                    }));
                                }}
                            >
                                <option value="">اختر المرحلة</option>
                                {levels.map((level) => (
                                    <option key={level.id} value={level.id}>
                                        {level.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.education_level_id} />
                        </div>

                        {data.education_level_id !== '' && (
                            <div className="grid gap-2">
                                <Label>المادة</Label>
                                <select
                                    className={selectClass}
                                    value={data.subject_id}
                                    onChange={(e) => {
                                        setData((prev) => ({
                                            ...prev,
                                            subject_id:
                                                e.target.value === ''
                                                    ? ''
                                                    : Number(e.target.value),
                                            teacher_id: '',
                                            teacher_schedule_ids: [],
                                        }));
                                    }}
                                >
                                    <option value="">اختر المادة</option>
                                    {activeSubjects.map((subject) => (
                                        <option
                                            key={subject.id}
                                            value={subject.id}
                                        >
                                            {subject.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.subject_id} />
                            </div>
                        )}

                        {data.subject_id !== '' && (
                            <div className="grid gap-2">
                                <Label>المعلم</Label>
                                {availableTeachers.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        لا يوجد معلم يدرّس هذه المادة في هذه
                                        المرحلة.
                                    </p>
                                ) : (
                                    <select
                                        className={selectClass}
                                        value={data.teacher_id}
                                        onChange={(e) => {
                                            setData((prev) => ({
                                                ...prev,
                                                teacher_id:
                                                    e.target.value === ''
                                                        ? ''
                                                        : Number(e.target.value),
                                                teacher_schedule_ids: [],
                                            }));
                                        }}
                                    >
                                        <option value="">اختر المعلم</option>
                                        {availableTeachers.map((teacher) => (
                                            <option
                                                key={teacher.id}
                                                value={teacher.id}
                                            >
                                                {teacher.name}
                                            </option>
                                        ))}
                                    </select>
                                )}
                                <InputError message={errors.teacher_id} />
                            </div>
                        )}

                        {data.teacher_id !== '' && (
                            <div className="grid gap-2">
                                <Label>المواعيد</Label>
                                {availableSchedules.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        لا توجد مواعيد مسجّلة لهذا المعلم.
                                    </p>
                                ) : (
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {availableSchedules.map((schedule) => (
                                            <label
                                                key={schedule.id}
                                                className="flex cursor-pointer items-center gap-2 rounded-md border bg-background p-2 text-sm"
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="size-4"
                                                    checked={data.teacher_schedule_ids.includes(
                                                        schedule.id,
                                                    )}
                                                    onChange={() =>
                                                        toggleSchedule(
                                                            schedule.id,
                                                        )
                                                    }
                                                />
                                                {DAYS[schedule.day_of_week]}{' '}
                                                <span className="dir-ltr">
                                                    {schedule.starts_at}–
                                                    {schedule.ends_at}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                )}
                                <InputError
                                    message={errors.teacher_schedule_ids}
                                />
                            </div>
                        )}
                    </div>

                    <Button disabled={processing}>تسجيل الحجز</Button>
                </form>
            </div>
        </>
    );
}
