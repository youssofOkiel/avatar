import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState, type CSSProperties } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { dayLabel, formatTimeRange12 } from '@/lib/datetime';
import {
    dayTimeBounds,
    dayRowHeight,
    layoutColumnItems,
} from '@/lib/schedule-timeline';

type Kind = 'teacher' | 'external' | 'session';

type SessionStatus = 'pending' | 'recorded' | 'upcoming' | 'canceled';

type Item = {
    kind: Kind;
    title: string | null;
    who: string | null;
    level: string | null;
    level_group: string | null;
    education_level_id: number | null;
    education_level_group_id: number | null;
    room_id: number | null;
    starts_at: string;
    ends_at: string;
    has_level_conflict: boolean;
    has_room_conflict: boolean;
    occurrence_kind: 'teacher' | 'external' | 'session';
    occurrence_id: number;
    session_id: number | null;
    actual_income: number | null;
    session_status: SessionStatus;
};

type Room = { id: number; name: string };
type Level = { id: number; name: string };
type LevelGroup = { id: number; name: string; levels: Level[] };

type Day = {
    date: string;
    day_of_week: number;
    is_today: boolean;
    cells: { room_id: number; items: Item[] }[];
    unassigned: Item[];
};

type Week = {
    start: string;
    end: string;
    prev: string;
    next: string;
    today: string;
};

type SelectedEvent = {
    item: Item;
    dayName: string;
    date: string;
    roomName: string;
};

const KIND_LABEL: Record<Kind, string> = {
    teacher: 'معلم',
    external: 'محاضر خارجي',
    session: 'حجز قاعة',
};

const KIND_CLASS: Record<Kind, string> = {
    teacher: 'bg-slate-100 text-slate-800 border-slate-200',
    external: 'bg-orange-100 text-orange-900 border-orange-300',
    session: 'bg-stone-100 text-stone-800 border-stone-300',
};

/** Distinct colors per education-level group (avoid amber/emerald used by non-teacher kinds). */
const GROUP_SWATCH = [
    'bg-blue-200 border-blue-400',
    'bg-violet-200 border-violet-400',
    'bg-rose-200 border-rose-400',
    'bg-cyan-200 border-cyan-400',
    'bg-lime-200 border-lime-400',
] as const;

const GROUP_PALETTE = [
    'bg-blue-100 text-blue-900 border-blue-300',
    'bg-violet-100 text-violet-900 border-violet-300',
    'bg-rose-100 text-rose-900 border-rose-300',
    'bg-cyan-100 text-cyan-900 border-cyan-300',
    'bg-lime-100 text-lime-900 border-lime-300',
] as const;

function groupSwatchClass(
    groupId: number,
    groupIndexById: Map<number, number>,
): string {
    const index = groupIndexById.get(groupId);

    if (index === undefined) {
        return GROUP_SWATCH[0];
    }

    return GROUP_SWATCH[index % GROUP_SWATCH.length];
}

function groupColorClass(
    groupId: number | null,
    groupIndexById: Map<number, number>,
): string {
    if (groupId === null) {
        return KIND_CLASS.teacher;
    }

    const index = groupIndexById.get(groupId);

    if (index === undefined) {
        return KIND_CLASS.teacher;
    }

    return GROUP_PALETTE[index % GROUP_PALETTE.length];
}

function cardColorClass(
    item: Item,
    groupIndexById: Map<number, number>,
): string {
    if (item.kind === 'teacher') {
        return groupColorClass(item.education_level_group_id, groupIndexById);
    }

    return KIND_CLASS[item.kind];
}

const selectClass =
    'border-input text-foreground h-10 w-full rounded-md border bg-background px-3 text-sm sm:w-56';

function money(value: number): string {
    return `${value.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ج.م`;
}

function sessionStatusLabel(status: SessionStatus): string {
    if (status === 'canceled') {
        return 'ملغاة';
    }

    if (status === 'recorded') {
        return 'مكتملة';
    }

    if (status === 'pending') {
        return 'بانتظار تسجيل الإيراد';
    }

    return 'قادمة';
}

function itemsMatch(a: Item, b: Item): boolean {
    return (
        a.occurrence_kind === b.occurrence_kind &&
        a.occurrence_id === b.occurrence_id &&
        a.starts_at === b.starts_at &&
        a.ends_at === b.ends_at
    );
}

function findItemInDays(days: Day[], selected: SelectedEvent): Item | null {
    const day = days.find((entry) => entry.date === selected.date);

    if (!day) {
        return null;
    }

    return allDayItems(day).find((item) => itemsMatch(item, selected.item)) ?? null;
}

function statusAfterRestore(item: Item, date: string): SessionStatus {
    const [hours, minutes] = item.starts_at.split(':').map(Number);
    const startsAt = new Date(`${date}T00:00:00`);
    startsAt.setHours(hours, minutes, 0, 0);

    return startsAt <= new Date() ? 'pending' : 'upcoming';
}

function outcomeUrl(selected: SelectedEvent): string {
    const params = new URLSearchParams({
        kind: selected.item.occurrence_kind,
        id: String(selected.item.occurrence_id),
        date: selected.date,
    });

    return `/admin/sessions/outcome?${params.toString()}`;
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('ar-EG', {
        day: 'numeric',
        month: 'short',
    });
}

function allDayItems(day: Day): Item[] {
    return [...day.cells.flatMap((cell) => cell.items), ...day.unassigned];
}

function uniqueSorted(values: (string | null | undefined)[]): string[] {
    return [...new Set(values.filter((value): value is string => Boolean(value)))]
        .sort((a, b) => a.localeCompare(b, 'ar'));
}

function matchesFilters(
    item: Item,
    teacher: string,
    subject: string,
    levelGroupId: number | '',
): boolean {
    if (levelGroupId !== '' && item.kind === 'teacher') {
        if (item.education_level_group_id !== levelGroupId) {
            return false;
        }
    }

    if (teacher !== '' && item.who !== teacher) {
        return false;
    }

    if (subject !== '' && item.title !== subject) {
        return false;
    }

    return true;
}

function filterDay(
    day: Day,
    teacher: string,
    subject: string,
    levelGroupId: number | '',
): Day {
    return {
        ...day,
        cells: day.cells.map((cell) => ({
            ...cell,
            items: cell.items.filter((item) =>
                matchesFilters(item, teacher, subject, levelGroupId),
            ),
        })),
        unassigned: day.unassigned.filter((item) =>
            matchesFilters(item, teacher, subject, levelGroupId),
        ),
    };
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1 border-b border-border/60 py-3 last:border-b-0">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm font-medium">{value}</dd>
        </div>
    );
}

function EventCard({
    item,
    style,
    groupIndexById,
    onSelect,
}: {
    item: Item;
    style?: CSSProperties;
    groupIndexById: Map<number, number>;
    onSelect: () => void;
}) {
    const isCanceled = item.session_status === 'canceled';

    return (
        <button
            type="button"
            style={style}
            onClick={onSelect}
            className={`absolute start-1 end-1 h-auto cursor-pointer rounded-md border p-2 text-start text-xs shadow-sm transition hover:brightness-95 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
                isCanceled
                    ? 'border-dashed border-muted-foreground/40 bg-muted/70 text-muted-foreground opacity-80'
                    : cardColorClass(item, groupIndexById)
            } ${
                item.has_level_conflict || item.has_room_conflict
                    ? 'border-2 border-destructive z-[2]'
                    : 'z-[1]'
            }`}
        >
            <div className="flex items-start justify-between gap-1">
                <span
                    className={`font-semibold tabular-nums leading-snug ${isCanceled ? 'line-through' : ''}`}
                >
                    {formatTimeRange12(item.starts_at, item.ends_at)}
                </span>
                <span className="flex shrink-0 flex-col items-end gap-0.5">
                    {(item.has_level_conflict || item.has_room_conflict) && (
                        <span className="rounded bg-destructive/15 px-1.5 py-0.5 text-[10px] font-semibold text-destructive">
                            تعارض
                        </span>
                    )}
                    {isCanceled && (
                        <span className="rounded bg-destructive/15 px-1.5 py-0.5 text-[10px] font-semibold text-destructive">
                            ملغاة
                        </span>
                    )}
                    <span className="text-[10px] opacity-80">
                        {KIND_LABEL[item.kind]}
                    </span>
                </span>
            </div>
            <div
                className={`mt-1 font-medium leading-snug ${isCanceled ? 'line-through' : ''}`}
            >
                {item.title ?? '—'}
            </div>
            {item.who && (
                <div
                    className={`mt-0.5 leading-snug opacity-80 ${isCanceled ? 'line-through' : ''}`}
                >
                    {item.who}
                </div>
            )}
            {item.level_group && (
                <div
                    className={`mt-0.5 leading-snug opacity-80 ${isCanceled ? 'line-through' : ''}`}
                >
                    {item.level_group}
                    {item.level ? ` — ${item.level}` : ''}
                </div>
            )}
            {!item.level_group && item.level && (
                <div
                    className={`mt-0.5 leading-snug opacity-80 ${isCanceled ? 'line-through' : ''}`}
                >
                    {item.level}
                </div>
            )}
        </button>
    );
}

function TimelineColumn({
    items,
    bounds,
    height,
    groupIndexById,
    onSelect,
}: {
    items: Item[];
    bounds: NonNullable<ReturnType<typeof dayTimeBounds>>;
    height: number;
    groupIndexById: Map<number, number>;
    onSelect: (item: Item) => void;
}) {
    if (items.length === 0) {
        return (
            <div className="relative overflow-hidden" style={{ height }}>
                <span className="text-muted-foreground/40 absolute inset-0 flex items-center justify-center text-xs">
                    —
                </span>
            </div>
        );
    }

    const laidOut = layoutColumnItems(items, bounds);

    return (
        <div className="relative overflow-hidden" style={{ height }}>
            {laidOut.map(({ item, top, minHeight }, i) => (
                <EventCard
                    key={`${item.starts_at}-${item.ends_at}-${item.title}-${i}`}
                    item={item}
                    style={{ top, minHeight }}
                    groupIndexById={groupIndexById}
                    onSelect={() => onSelect(item)}
                />
            ))}
        </div>
    );
}

export default function ScheduleIndex({
    week,
    rooms,
    levelGroups,
    days,
}: {
    week: Week;
    rooms: Room[];
    levelGroups: LevelGroup[];
    days: Day[];
}) {
    const [teacherFilter, setTeacherFilter] = useState('');
    const [subjectFilter, setSubjectFilter] = useState('');
    const [levelGroupFilter, setLevelGroupFilter] = useState<number | ''>('');
    const [selected, setSelected] = useState<SelectedEvent | null>(null);
    const [occurrenceProcessing, setOccurrenceProcessing] = useState(false);

    useEffect(() => {
        setSelected((current) => {
            if (!current) {
                return null;
            }

            const updatedItem = findItemInDays(days, current);

            if (!updatedItem) {
                return current;
            }

            if (
                updatedItem.session_status === current.item.session_status &&
                updatedItem.actual_income === current.item.actual_income &&
                updatedItem.session_id === current.item.session_id
            ) {
                return current;
            }

            return { ...current, item: updatedItem };
        });
    }, [days]);

    const cancelSessionOccurrence = (event: SelectedEvent) => {
        const previous = event;

        setOccurrenceProcessing(true);
        setSelected({
            ...event,
            item: { ...event.item, session_status: 'canceled' },
        });

        router.post(
            '/admin/sessions/occurrence/cancel',
            {
                kind: event.item.occurrence_kind,
                id: event.item.occurrence_id,
                date: event.date,
            },
            {
                preserveScroll: true,
                onFinish: () => setOccurrenceProcessing(false),
                onError: () => setSelected(previous),
            },
        );
    };

    const restoreSessionOccurrence = (event: SelectedEvent) => {
        const previous = event;

        setOccurrenceProcessing(true);
        setSelected({
            ...event,
            item: {
                ...event.item,
                session_status: statusAfterRestore(event.item, event.date),
            },
        });

        router.post(
            '/admin/sessions/occurrence/restore',
            {
                kind: event.item.occurrence_kind,
                id: event.item.occurrence_id,
                date: event.date,
            },
            {
                preserveScroll: true,
                onFinish: () => setOccurrenceProcessing(false),
                onError: () => setSelected(previous),
            },
        );
    };

    const roomNameById = useMemo(() => {
        const map = new Map<number, string>();

        for (const room of rooms) {
            map.set(room.id, room.name);
        }

        return map;
    }, [rooms]);

    const groupIndexById = useMemo(() => {
        const map = new Map<number, number>();

        levelGroups.forEach((group, index) => {
            map.set(group.id, index);
        });

        return map;
    }, [levelGroups]);

    const teachers = useMemo(
        () => uniqueSorted(days.flatMap((day) => allDayItems(day).map((item) => item.who))),
        [days],
    );

    const subjects = useMemo(
        () =>
            uniqueSorted(
                days.flatMap((day) => allDayItems(day).map((item) => item.title)),
            ),
        [days],
    );

    const filteredDays = useMemo(
        () =>
            days.map((day) =>
                filterDay(day, teacherFilter, subjectFilter, levelGroupFilter),
            ),
        [days, teacherFilter, subjectFilter, levelGroupFilter],
    );

    const showUnassigned = filteredDays.some(
        (day) => day.unassigned.length > 0,
    );

    return (
        <>
            <Head title="الجدول" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="الجدول"
                    description={`الأسبوع من ${formatDate(week.start)} إلى ${formatDate(week.end)}`}
                    actions={
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/admin/schedule?date=${week.prev}`}>
                                    الأسبوع السابق
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={`/admin/schedule?date=${week.today}`}
                                >
                                    هذا الأسبوع
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/admin/schedule?date=${week.next}`}>
                                    الأسبوع التالي
                                </Link>
                            </Button>
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="schedule-level-group-filter">
                            مجموعة المرحلة
                        </Label>
                        <select
                            id="schedule-level-group-filter"
                            className={selectClass}
                            value={levelGroupFilter}
                            onChange={(e) =>
                                setLevelGroupFilter(
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                        >
                            <option value="">كل المجموعات</option>
                            {levelGroups.map((group) => (
                                <option key={group.id} value={group.id}>
                                    {group.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="schedule-teacher-filter">المعلم</Label>
                        <select
                            id="schedule-teacher-filter"
                            className={selectClass}
                            value={teacherFilter}
                            onChange={(e) => setTeacherFilter(e.target.value)}
                        >
                            <option value="">كل المعلمين</option>
                            {teachers.map((name) => (
                                <option key={name} value={name}>
                                    {name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="schedule-subject-filter">المادة</Label>
                        <select
                            id="schedule-subject-filter"
                            className={selectClass}
                            value={subjectFilter}
                            onChange={(e) => setSubjectFilter(e.target.value)}
                        >
                            <option value="">كل المواد</option>
                            {subjects.map((name) => (
                                <option key={name} value={name}>
                                    {name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
                    <span className="text-muted-foreground font-medium">
                        ألوان مجموعات المراحل:
                    </span>
                    {levelGroups.map((group) => (
                        <span
                            key={group.id}
                            className="inline-flex items-center gap-1.5"
                        >
                            <span
                                className={`size-3 shrink-0 rounded-sm border ${groupSwatchClass(group.id, groupIndexById)}`}
                                aria-hidden
                            />
                            {group.name}
                        </span>
                    ))}
                    <span className="inline-flex items-center gap-1.5">
                        <span
                            className="size-3 shrink-0 rounded-sm border bg-orange-200 border-orange-400"
                            aria-hidden
                        />
                        محاضر خارجي
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <span
                            className="size-3 shrink-0 rounded-sm border bg-stone-200 border-stone-400"
                            aria-hidden
                        />
                        حجز قاعة
                    </span>
                </div>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-sm">
                    <table className="w-full min-w-[720px] border-collapse text-sm">
                        <thead>
                            <tr className="bg-muted/60">
                                <th className="sticky start-0 z-20 min-w-28 border-b border-e bg-muted p-3 text-start font-medium">
                                    اليوم
                                </th>
                                {rooms.map((room) => (
                                    <th
                                        key={room.id}
                                        className="min-w-56 border-b p-3 text-start font-medium"
                                    >
                                        {room.name}
                                    </th>
                                ))}
                                {showUnassigned && (
                                    <th className="min-w-56 border-b p-3 text-start font-medium text-muted-foreground">
                                        بدون قاعة
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {filteredDays.map((day) => {
                                const items = allDayItems(day);
                                const bounds = dayTimeBounds(items);
                                const isEmpty = bounds === null;
                                const height =
                                    bounds === null
                                        ? 56
                                        : dayRowHeight(
                                              [
                                                  ...day.cells.map(
                                                      (cell) => cell.items,
                                                  ),
                                                  day.unassigned,
                                              ],
                                              bounds,
                                          );

                                const openItem = (
                                    item: Item,
                                    roomName: string,
                                ) => {
                                    setSelected({
                                        item,
                                        dayName: dayLabel(day.day_of_week),
                                        date: day.date,
                                        roomName,
                                    });
                                };

                                return (
                                    <tr
                                        key={day.date}
                                        className={
                                            day.is_today
                                                ? 'bg-primary/5'
                                                : 'odd:bg-background even:bg-muted/20'
                                        }
                                    >
                                        <th
                                            className={`sticky start-0 z-20 border-e border-t bg-card p-3 text-start align-top ${
                                                day.is_today
                                                    ? 'shadow-[inset_-3px_0_0_0_var(--color-primary)]'
                                                    : ''
                                            }`}
                                        >
                                            <div className="font-semibold">
                                                {dayLabel(day.day_of_week)}
                                            </div>
                                            <div className="text-muted-foreground text-xs font-normal">
                                                {formatDate(day.date)}
                                            </div>
                                        </th>
                                        {day.cells.map((cell) => (
                                            <td
                                                key={cell.room_id}
                                                className="relative z-0 overflow-hidden border-t p-1 align-top"
                                            >
                                                {isEmpty || !bounds ? (
                                                    <span className="text-muted-foreground/40 block py-4 text-center text-xs">
                                                        —
                                                    </span>
                                                ) : (
                                                    <TimelineColumn
                                                        items={cell.items}
                                                        bounds={bounds}
                                                        height={height}
                                                        groupIndexById={
                                                            groupIndexById
                                                        }
                                                        onSelect={(item) =>
                                                            openItem(
                                                                item,
                                                                roomNameById.get(
                                                                    cell.room_id,
                                                                ) ?? '—',
                                                            )
                                                        }
                                                    />
                                                )}
                                            </td>
                                        ))}
                                        {showUnassigned && (
                                            <td className="relative z-0 overflow-hidden border-t p-1 align-top">
                                                {isEmpty || !bounds ? (
                                                    <span className="text-muted-foreground/40 block py-4 text-center text-xs">
                                                        —
                                                    </span>
                                                ) : (
                                                    <TimelineColumn
                                                        items={day.unassigned}
                                                        bounds={bounds}
                                                        height={height}
                                                        groupIndexById={
                                                            groupIndexById
                                                        }
                                                        onSelect={(item) =>
                                                            openItem(
                                                                item,
                                                                'بدون قاعة',
                                                            )
                                                        }
                                                    />
                                                )}
                                            </td>
                                        )}
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>

            <Dialog
                open={selected !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelected(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    {selected && (
                        <>
                            <DialogHeader className="text-start sm:text-start pe-10">
                                <DialogTitle>
                                    {selected.item.title ?? 'موعد'}
                                </DialogTitle>
                                <DialogDescription>
                                    {formatTimeRange12(
                                        selected.item.starts_at,
                                        selected.item.ends_at,
                                    )}
                                </DialogDescription>
                            </DialogHeader>
                            <dl>
                                <DetailRow
                                    label="النوع"
                                    value={KIND_LABEL[selected.item.kind]}
                                />
                                <DetailRow
                                    label="اليوم"
                                    value={`${selected.dayName} — ${formatDate(selected.date)}`}
                                />
                                <DetailRow
                                    label="القاعة"
                                    value={selected.roomName}
                                />
                                {selected.item.who && (
                                    <DetailRow
                                        label={
                                            selected.item.kind === 'external'
                                                ? 'المحاضر'
                                                : 'المعلم'
                                        }
                                        value={selected.item.who}
                                    />
                                )}
                                {selected.item.level_group && (
                                    <DetailRow
                                        label="مجموعة المرحلة"
                                        value={selected.item.level_group}
                                    />
                                )}
                                {selected.item.level && (
                                    <DetailRow
                                        label="الصف"
                                        value={selected.item.level}
                                    />
                                )}
                                {selected.item.has_level_conflict && (
                                    <DetailRow
                                        label="تنبيه"
                                        value="يوجد تعارض في نفس الصف لهذه الفترة (لا يمكن للطالب حضور حصتين في نفس الوقت)"
                                    />
                                )}
                                {selected.item.has_room_conflict && (
                                    <DetailRow
                                        label="تنبيه"
                                        value="القاعة محجوزة في نفس الفترة الزمنية (معلم أو محاضر أو حجز قاعة)"
                                    />
                                )}
                                <DetailRow
                                    label="حالة الحصة"
                                    value={sessionStatusLabel(
                                        selected.item.session_status,
                                    )}
                                />
                                {selected.item.session_status === 'recorded' &&
                                    selected.item.actual_income !== null && (
                                        <DetailRow
                                            label="الإيراد الفعلي"
                                            value={money(
                                                selected.item.actual_income,
                                            )}
                                        />
                                    )}
                            </dl>
                            {(selected.item.session_status === 'pending' ||
                                selected.item.session_status ===
                                    'recorded') && (
                                <div className="flex gap-2">
                                    <Button asChild className="flex-1">
                                        <Link href={outcomeUrl(selected)}>
                                            {selected.item.session_status ===
                                            'recorded'
                                                ? 'عرض / تعديل الإيراد'
                                                : 'تسجيل الإيراد'}
                                        </Link>
                                    </Button>
                                    {selected.item.session_status ===
                                        'pending' && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="flex-1"
                                            disabled={occurrenceProcessing}
                                            onClick={() =>
                                                cancelSessionOccurrence(selected)
                                            }
                                        >
                                            إلغاء الحصة
                                        </Button>
                                    )}
                                </div>
                            )}
                            {(selected.item.session_status === 'upcoming' ||
                                selected.item.session_status ===
                                    'canceled') && (
                                <div className="flex gap-2">
                                    {selected.item.session_status ===
                                        'upcoming' && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="w-full"
                                            disabled={occurrenceProcessing}
                                            onClick={() =>
                                                cancelSessionOccurrence(selected)
                                            }
                                        >
                                            إلغاء الحصة
                                        </Button>
                                    )}
                                    {selected.item.session_status ===
                                        'canceled' && (
                                        <div className="flex gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className="flex-1"
                                                disabled={occurrenceProcessing}
                                                onClick={() =>
                                                    restoreSessionOccurrence(
                                                        selected,
                                                    )
                                                }
                                            >
                                                استعادة الحصة
                                            </Button>
                                            <Button
                                                asChild
                                                className="flex-1"
                                            >
                                                <Link href={outcomeUrl(selected)}>
                                                    تسجيل الإيراد
                                                </Link>
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            )}
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
