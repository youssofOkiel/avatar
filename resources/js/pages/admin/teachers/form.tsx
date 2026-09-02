import { Head, Link, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { WEEKDAY_OPTIONS } from '@/lib/datetime';
import { reportFormErrors } from '@/lib/form-feedback';

type SubjectOption = { id: number; name: string };
type Level = { id: number; name: string; subjects: SubjectOption[] };
type LevelGroup = { id: number; name: string; levels: Level[] };
type RoomOption = { id: number; name: string };
type Selection = { education_level_id: number; subject_id: number };
type Schedule = {
    education_level_id: number;
    subject_id: number;
    room_id: number | '' | null;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
};

type Teacher = {
    id: number;
    name: string;
    phone: string;
    bio: string | null;
    is_active: boolean;
    selections: Selection[];
    schedules: Schedule[];
} | null;

const selectClass =
    'border-input text-foreground h-10 rounded-md border bg-background px-3 text-sm';

const keyOf = (levelId: number, subjectId: number) => `${levelId}:${subjectId}`;

export default function TeacherForm({
    teacher,
    levelGroups,
    rooms,
}: {
    teacher: Teacher;
    levelGroups: LevelGroup[];
    rooms: RoomOption[];
}) {
    const isEdit = teacher !== null;

    const { data, setData, post, put, processing, errors } = useForm<{
        name: string;
        phone: string;
        bio: string;
        is_active: boolean;
        selections: Selection[];
        schedules: Schedule[];
    }>({
        name: teacher?.name ?? '',
        phone: teacher?.phone ?? '',
        bio: teacher?.bio ?? '',
        is_active: teacher?.is_active ?? true,
        selections: teacher?.selections ?? [],
        schedules: (teacher?.schedules ?? []).map((schedule) => ({
            ...schedule,
            room_id: schedule.room_id ?? '',
        })),
    });

    const allLevels = levelGroups.flatMap((group) => group.levels);

    const subjectName = (id: number): string => {
        for (const level of allLevels) {
            const found = level.subjects.find((s) => s.id === id);

            if (found) {
                return found.name;
            }
        }

        return '';
    };

    const levelName = (id: number): string =>
        allLevels.find((l) => l.id === id)?.name ?? '';

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
                room_id: '',
                day_of_week: WEEKDAY_OPTIONS[0].value,
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

    const scheduleError = (index: number): string | undefined => {
        const flat = errors as Record<string, string>;

        return (
            flat[`schedules.${index}.starts_at`] ??
            flat[`schedules.${index}.ends_at`] ??
            flat[`schedules.${index}.room_id`]
        );
    };

    const scheduleHasConflict = (index: number): boolean => {
        const current = data.schedules[index];

        if (!current?.starts_at || !current?.ends_at) {
            return false;
        }

        return data.schedules.some((other, otherIndex) => {
            if (otherIndex === index) {
                return false;
            }

            if (
                other.education_level_id !== current.education_level_id ||
                other.day_of_week !== current.day_of_week
            ) {
                return false;
            }

            if (!other.starts_at || !other.ends_at) {
                return false;
            }

            return (
                other.starts_at < current.ends_at &&
                other.ends_at > current.starts_at
            );
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = {
            onError: reportFormErrors,
        };

        if (isEdit) {
            put(`/admin/teachers/${teacher.id}`, options);
        } else {
            post('/admin/teachers', options);
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
                            <Label htmlFor="phone">رقم الهاتف</Label>
                            <Input
                                id="phone"
                                type="tel"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                required
                                dir="ltr"
                                className="text-start"
                            />
                            <InputError message={errors.phone} />
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

                        {levelGroups.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                لا توجد مراحل أو مواد مُعرَّفة في النظام.
                            </p>
                        ) : (
                            <div className="space-y-6">
                                {levelGroups.map((group) => (
                                    <div key={group.id} className="space-y-4">
                                        <h3 className="text-sm font-semibold text-primary">
                                            {group.name}
                                        </h3>
                                        {group.levels.map((level) => (
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
                                {data.schedules.map((schedule, index) => {
                                    const invalid =
                                        scheduleHasConflict(index) ||
                                        Boolean(scheduleError(index));

                                    return (
                                    <div
                                        key={index}
                                        data-invalid={invalid ? 'true' : undefined}
                                        className={`flex flex-wrap items-end gap-3 rounded-md border bg-background p-3 ${
                                            invalid ? 'border-destructive' : ''
                                        }`}
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
                                                القاعة
                                            </Label>
                                            <select
                                                className={selectClass}
                                                value={schedule.room_id ?? ''}
                                                onChange={(e) =>
                                                    updateSchedule(index, {
                                                        room_id: e.target.value
                                                            ? Number(
                                                                  e.target.value,
                                                              )
                                                            : '',
                                                    })
                                                }
                                            >
                                                <option value="">
                                                    بدون قاعة
                                                </option>
                                                {rooms.map((room) => (
                                                    <option
                                                        key={room.id}
                                                        value={room.id}
                                                    >
                                                        {room.name}
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
                                                {WEEKDAY_OPTIONS.map((day) => (
                                                    <option
                                                        key={day.value}
                                                        value={day.value}
                                                    >
                                                        {day.label}
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
                                        {scheduleError(index) && (
                                            <p className="w-full text-sm text-destructive">
                                                {scheduleError(index)}
                                            </p>
                                        )}
                                    </div>
                                    );
                                })}
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
