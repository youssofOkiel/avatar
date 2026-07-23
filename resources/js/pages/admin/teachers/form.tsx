import { Head, Link, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type SubjectOption = { id: number; name: string };
type Level = { id: number; name: string; subjects: SubjectOption[] };
type Selection = { education_level_id: number; subject_id: number };
type Schedule = {
    education_level_id: number;
    subject_id: number;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
};

type Teacher = {
    id: number;
    name: string;
    bio: string | null;
    is_active: boolean;
    selections: Selection[];
    schedules: Schedule[];
} | null;

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
    'border-input text-foreground h-10 rounded-md border bg-background px-3 text-sm';

const keyOf = (levelId: number, subjectId: number) => `${levelId}:${subjectId}`;

export default function TeacherForm({
    teacher,
    levels,
}: {
    teacher: Teacher;
    levels: Level[];
}) {
    const isEdit = teacher !== null;

    const { data, setData, post, put, processing, errors } = useForm<{
        name: string;
        bio: string;
        is_active: boolean;
        selections: Selection[];
        schedules: Schedule[];
    }>({
        name: teacher?.name ?? '',
        bio: teacher?.bio ?? '',
        is_active: teacher?.is_active ?? true,
        selections: teacher?.selections ?? [],
        schedules: teacher?.schedules ?? [],
    });

    const subjectName = (id: number): string => {
        for (const level of levels) {
            const found = level.subjects.find((s) => s.id === id);

            if (found) {
                return found.name;
            }
        }

        return '';
    };

    const levelName = (id: number): string =>
        levels.find((l) => l.id === id)?.name ?? '';

    const isSelected = (levelId: number, subjectId: number): boolean =>
        data.selections.some(
            (s) =>
                s.education_level_id === levelId && s.subject_id === subjectId,
        );

    const toggleSelection = (levelId: number, subjectId: number) => {
        if (isSelected(levelId, subjectId)) {
            setData((prev) => ({
                ...prev,
                selections: prev.selections.filter(
                    (s) =>
                        !(
                            s.education_level_id === levelId &&
                            s.subject_id === subjectId
                        ),
                ),
                schedules: prev.schedules.filter(
                    (s) =>
                        !(
                            s.education_level_id === levelId &&
                            s.subject_id === subjectId
                        ),
                ),
            }));
        } else {
            setData('selections', [
                ...data.selections,
                { education_level_id: levelId, subject_id: subjectId },
            ]);
        }
    };

    const addSchedule = () => {
        if (data.selections.length === 0) {
            return;
        }

        const first = data.selections[0];

        setData('schedules', [
            ...data.schedules,
            {
                education_level_id: first.education_level_id,
                subject_id: first.subject_id,
                day_of_week: 0,
                starts_at: '16:00',
                ends_at: '17:00',
            },
        ]);
    };

    const updateSchedule = (
        index: number,
        patch: Partial<Schedule>,
    ) => {
        setData(
            'schedules',
            data.schedules.map((s, i) => (i === index ? { ...s, ...patch } : s)),
        );
    };

    const removeSchedule = (index: number) => {
        setData(
            'schedules',
            data.schedules.filter((_, i) => i !== index),
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            put(`/admin/teachers/${teacher.id}`);
        } else {
            post('/admin/teachers');
        }
    };

    return (
        <>
            <Head title={isEdit ? 'تعديل معلم' : 'إضافة معلم'} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={isEdit ? 'تعديل معلم' : 'إضافة معلم'}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/teachers">رجوع</Link>
                        </Button>
                    }
                />
                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-5 rounded-xl border bg-card p-5 shadow-sm">
                        <div className="grid gap-2">
                            <Label htmlFor="name">اسم المعلم</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoFocus
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="bio">نبذة (اختياري)</Label>
                            <textarea
                                id="bio"
                                value={data.bio}
                                onChange={(e) => setData('bio', e.target.value)}
                                rows={3}
                                className="border-input text-foreground min-h-20 rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            <InputError message={errors.bio} />
                        </div>

                        <label className="flex cursor-pointer items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                className="size-4"
                                checked={data.is_active}
                                onChange={(e) =>
                                    setData('is_active', e.target.checked)
                                }
                            />
                            معلم نشط
                        </label>
                    </div>

                    <div className="space-y-4 rounded-xl border bg-card p-5 shadow-sm">
                        <div>
                            <h2 className="text-base font-semibold text-primary">
                                المواد التي يدرّسها
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                اختر المواد ضمن كل مرحلة دراسية بشكل مستقل.
                            </p>
                        </div>

                        {levels.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                لا توجد مواد. أضف المراحل والمواد أولًا.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {levels.map((level) => (
                                    <div key={level.id} className="space-y-2">
                                        <div className="text-sm font-medium">
                                            {level.name}
                                        </div>
                                        {level.subjects.length === 0 ? (
                                            <p className="text-muted-foreground text-xs">
                                                لا توجد مواد لهذه المرحلة.
                                            </p>
                                        ) : (
                                            <div className="grid gap-2 sm:grid-cols-3">
                                                {level.subjects.map(
                                                    (subject) => (
                                                        <label
                                                            key={keyOf(
                                                                level.id,
                                                                subject.id,
                                                            )}
                                                            className="flex cursor-pointer items-center gap-2 rounded-md border bg-background p-2 text-sm"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                className="size-4"
                                                                checked={isSelected(
                                                                    level.id,
                                                                    subject.id,
                                                                )}
                                                                onChange={() =>
                                                                    toggleSelection(
                                                                        level.id,
                                                                        subject.id,
                                                                    )
                                                                }
                                                            />
                                                            {subject.name}
                                                        </label>
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                        <InputError message={errors.selections} />
                    </div>

                    <div className="space-y-4 rounded-xl border bg-card p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-primary">
                                    المواعيد الأسبوعية
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    حدد موعد كل مادة يدرّسها المعلم.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addSchedule}
                                disabled={data.selections.length === 0}
                            >
                                إضافة موعد
                            </Button>
                        </div>

                        {data.schedules.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                لا توجد مواعيد مضافة.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {data.schedules.map((schedule, index) => (
                                    <div
                                        key={index}
                                        className="flex flex-wrap items-end gap-3 rounded-md border bg-background p-3"
                                    >
                                        <div className="grid gap-1">
                                            <Label className="text-xs">
                                                المادة والمرحلة
                                            </Label>
                                            <select
                                                className={selectClass}
                                                value={keyOf(
                                                    schedule.education_level_id,
                                                    schedule.subject_id,
                                                )}
                                                onChange={(e) => {
                                                    const [lvl, subj] =
                                                        e.target.value
                                                            .split(':')
                                                            .map(Number);
                                                    updateSchedule(index, {
                                                        education_level_id: lvl,
                                                        subject_id: subj,
                                                    });
                                                }}
                                            >
                                                {data.selections.map((sel) => (
                                                    <option
                                                        key={keyOf(
                                                            sel.education_level_id,
                                                            sel.subject_id,
                                                        )}
                                                        value={keyOf(
                                                            sel.education_level_id,
                                                            sel.subject_id,
                                                        )}
                                                    >
                                                        {subjectName(
                                                            sel.subject_id,
                                                        )}{' '}
                                                        —{' '}
                                                        {levelName(
                                                            sel.education_level_id,
                                                        )}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid gap-1">
                                            <Label className="text-xs">
                                                اليوم
                                            </Label>
                                            <select
                                                className={selectClass}
                                                value={schedule.day_of_week}
                                                onChange={(e) =>
                                                    updateSchedule(index, {
                                                        day_of_week: Number(
                                                            e.target.value,
                                                        ),
                                                    })
                                                }
                                            >
                                                {DAYS.map((day, i) => (
                                                    <option key={i} value={i}>
                                                        {day}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid gap-1">
                                            <Label className="text-xs">من</Label>
                                            <Input
                                                type="time"
                                                className="w-32"
                                                value={schedule.starts_at}
                                                onChange={(e) =>
                                                    updateSchedule(index, {
                                                        starts_at:
                                                            e.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-1">
                                            <Label className="text-xs">
                                                إلى
                                            </Label>
                                            <Input
                                                type="time"
                                                className="w-32"
                                                value={schedule.ends_at}
                                                onChange={(e) =>
                                                    updateSchedule(index, {
                                                        ends_at: e.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => removeSchedule(index)}
                                        >
                                            <Trash2 className="size-4 text-destructive" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <Button disabled={processing}>
                        {isEdit ? 'حفظ' : 'إضافة'}
                    </Button>
                </form>
            </div>
        </>
    );
}
