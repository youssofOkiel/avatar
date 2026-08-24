export const PX_PER_MINUTE = 1.35;

/** Vertical gap between adjacent cards so they never sit flush. */
export const CARD_GAP = 6;

export type TimedItem = {
    starts_at: string;
    ends_at: string;
};

export type CardContent = {
    title?: string | null;
    who?: string | null;
    level?: string | null;
};

export type DayTimeBounds = {
    startMin: number;
    endMin: number;
};

export type LaidOutCard<T> = {
    item: T;
    top: number;
    minHeight: number;
};

/** Parse H:i or H:i:s into minutes from midnight. */
export function timeToMinutes(value: string): number {
    const match = value.match(/(\d{1,2}):(\d{2})/);

    if (!match) {
        return 0;
    }

    return Number(match[1]) * 60 + Number(match[2]);
}

/**
 * Normalize a time range. Inverted overnight ends (ends <= starts) get a
 * minimum 30-minute slot so layout does not collapse.
 */
export function itemRangeMinutes(item: TimedItem): {
    startMin: number;
    endMin: number;
} {
    const startMin = timeToMinutes(item.starts_at);
    let endMin = timeToMinutes(item.ends_at);

    if (endMin <= startMin) {
        endMin = startMin + 30;
    }

    return { startMin, endMin };
}

export function dayTimeBounds(items: TimedItem[]): DayTimeBounds | null {
    if (items.length === 0) {
        return null;
    }

    let startMin = Number.POSITIVE_INFINITY;
    let endMin = Number.NEGATIVE_INFINITY;

    for (const item of items) {
        const range = itemRangeMinutes(item);
        startMin = Math.min(startMin, range.startMin);
        endMin = Math.max(endMin, range.endMin);
    }

    if (!Number.isFinite(startMin) || !Number.isFinite(endMin) || endMin <= startMin) {
        return null;
    }

    return { startMin, endMin };
}

export function timelineHeight(bounds: DayTimeBounds): number {
    return (bounds.endMin - bounds.startMin) * PX_PER_MINUTE;
}

/** Minimum pixel height so every card line (time, title, teacher, level) fits. */
export function estimatedCardHeight(item: CardContent): number {
    let height = 52;

    if (item.who) {
        height += 18;
    }

    if (item.level) {
        height += 18;
    }

    return height;
}

function itemSlotPosition(
    item: TimedItem,
    bounds: DayTimeBounds,
): { top: number; slotMinHeight: number } {
    const range = itemRangeMinutes(item);
    const slotTop = (range.startMin - bounds.startMin) * PX_PER_MINUTE;
    const slotHeight = (range.endMin - range.startMin) * PX_PER_MINUTE;

    return {
        top: slotTop + CARD_GAP / 2,
        slotMinHeight: Math.max(slotHeight - CARD_GAP, CARD_GAP * 2),
    };
}

/**
 * Lay out cards by start time. Each card grows to fit its content; if a card
 * would overlap the one above, it is pushed down with CARD_GAP between them.
 */
export function layoutColumnItems<T extends TimedItem & CardContent>(
    items: T[],
    bounds: DayTimeBounds,
): LaidOutCard<T>[] {
    if (items.length === 0) {
        return [];
    }

    const sorted = [...items].sort((a, b) => {
        const rangeA = itemRangeMinutes(a);
        const rangeB = itemRangeMinutes(b);

        return (
            rangeA.startMin - rangeB.startMin ||
            rangeA.endMin - rangeB.endMin
        );
    });

    let lastBottom = 0;
    const laidOut: LaidOutCard<T>[] = [];

    for (const item of sorted) {
        const { top: idealTop, slotMinHeight } = itemSlotPosition(item, bounds);
        const minHeight = Math.max(
            slotMinHeight,
            estimatedCardHeight(item),
        );
        const top =
            lastBottom === 0
                ? idealTop
                : Math.max(idealTop, lastBottom + CARD_GAP);

        laidOut.push({ item, top, minHeight });
        lastBottom = top + minHeight;
    }

    return laidOut;
}

export function columnLayoutHeight<T extends TimedItem & CardContent>(
    items: T[],
    bounds: DayTimeBounds,
): number {
    const laidOut = layoutColumnItems(items, bounds);

    if (laidOut.length === 0) {
        return Math.max(timelineHeight(bounds), 80);
    }

    const last = laidOut[laidOut.length - 1];

    return Math.max(
        timelineHeight(bounds),
        80,
        last.top + last.minHeight + CARD_GAP / 2,
    );
}

export function dayRowHeight<T extends TimedItem & CardContent>(
    columns: T[][],
    bounds: DayTimeBounds,
): number {
    let height = Math.max(timelineHeight(bounds), 80);

    for (const items of columns) {
        height = Math.max(height, columnLayoutHeight(items, bounds));
    }

    return height;
}
