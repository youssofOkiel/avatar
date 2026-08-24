/** Weekday options ordered Saturday → Friday (Egyptian week). Values match Carbon dayOfWeek (0=Sun … 6=Sat). */
export const WEEKDAY_OPTIONS = [
    { value: 6, label: 'السبت' },
    { value: 0, label: 'الأحد' },
    { value: 1, label: 'الإثنين' },
    { value: 2, label: 'الثلاثاء' },
    { value: 3, label: 'الأربعاء' },
    { value: 4, label: 'الخميس' },
    { value: 5, label: 'الجمعة' },
] as const;

const DAY_LABELS: Record<number, string> = Object.fromEntries(
    WEEKDAY_OPTIONS.map((day) => [day.value, day.label]),
);

export function dayLabel(dayOfWeek: number): string {
    return DAY_LABELS[dayOfWeek] ?? '';
}

/**
 * Format a time string (H:i, H:i:s, or ISO datetime) as 12-hour Arabic (ص/م).
 */
export function formatTime12(value: string): string {
    const match = value.match(/(\d{1,2}):(\d{2})(?::\d{2})?/);

    if (!match) {
        return value;
    }

    let hours = Number(match[1]);
    const minutes = match[2];
    const period = hours >= 12 ? 'م' : 'ص';
    hours = hours % 12 || 12;

    return `${hours}:${minutes} ${period}`;
}

export function formatTimeRange12(startsAt: string, endsAt: string): string {
    return `${formatTime12(startsAt)}–${formatTime12(endsAt)}`;
}

export function formatDateTime12(value: string): string {
    return new Date(value).toLocaleString('ar-EG', {
        dateStyle: 'medium',
        timeStyle: 'short',
        hour12: true,
    });
}
