import { Head, Link, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { WEEKDAY_OPTIONS } from '@/lib/datetime';
import { reportFormErrors } from '@/lib/form-feedback';

type RoomOption = { id: number; name: string };
type Schedule = {
    topic: string;
    room_id: number | '' | null;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    income: number | '';
};

type Lecturer = {
    id: number;
    name: string;
    schedules: Schedule[];
} | null;

const selectClass =
    'border-input text-foreground h-10 rounded-md border bg-background px-3 text-sm';

export default function ExternalLecturerForm({
    lecturer,
    rooms,
}: {
    lecturer: Lecturer;
    rooms: RoomOption[];
}) {
    const isEdit = lecturer !== null;

    const { data, setData, post, put, processing, errors } = useForm<{
        name: string;
        schedules: Schedule[];
    }>({
        name: lecturer?.name ?? '',
        schedules: (lecturer?.schedules ?? []).map((schedule) => ({
            ...schedule,
            room_id: schedule.room_id ?? '',
        })),
    });

    const addSchedule = () => {
        setData('schedules', [
            ...data.schedules,
            {
                topic: '',
                room_id: '',
                day_of_week: WEEKDAY_OPTIONS[0].value,
                starts_at: '16:00',
                ends_at: '17:00',
                income: '',
            },
        ]);
    };

    const updateSchedule = (index: number, patch: Partial<Schedule>) => {
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
            flat[`schedules.${index}.topic`] ??
            flat[`schedules.${index}.ends_at`] ??
            flat[`schedules.${index}.starts_at`] ??
            flat[`schedules.${index}.room_id`] ??
            flat[`schedules.${index}.income`]
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = {
            onError: reportFormErrors,
        };

        if (isEdit) {
            put(`/admin/external-lecturers/${lecturer.id}`, options);
        } else {
            post('/admin/external-lecturers', options);
        }
    };

    return (
        <>
            <Head title={isEdit ? 'تعديل محاضر' : 'إضافة محاضر'} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={isEdit ? 'تعديل محاضر' : 'إضافة محاضر'}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/admin/external-lecturers">رجوع</Link>
                        </Button>
                    }
                />
                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-5 rounded-xl border bg-card p-5 shadow-sm">
                        <div className="grid gap-2">
                            <Label htmlFor="name">اسم المحاضر</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoFocus
                            />
                            <InputError message={errors.name} />
                        </div>
                    </div>

                    <div className="space-y-4 rounded-xl border bg-card p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-primary">
                                    المواعيد الأسبوعية
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    حدد موضوع كل موعد والقاعة والوقت.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addSchedule}
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
                                    const invalid = Boolean(scheduleError(index));

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
                                                الموضوع
                                            </Label>
                                            <Input
                                                className="w-48"
                                                value={schedule.topic}
                                                onChange={(e) =>
                                                    updateSchedule(index, {
                                                        topic: e.target.value,
                                                    })
                                                }
                                                placeholder="موضوع المحاضرة"
                                            />
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
