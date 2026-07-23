import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export function Pagination({ meta }: { meta: Omit<Paginated<unknown>, 'data'> }) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <span className="text-muted-foreground text-sm">
                عرض {meta.from ?? 0}–{meta.to ?? 0} من {meta.total}
            </span>
            <div className="flex items-center gap-2">
                {meta.prev_page_url ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={meta.prev_page_url} preserveScroll>
                            السابق
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        السابق
                    </Button>
                )}
                <span className="text-sm">
                    صفحة {meta.current_page} من {meta.last_page}
                </span>
                {meta.next_page_url ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={meta.next_page_url} preserveScroll>
                            التالي
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        التالي
                    </Button>
                )}
            </div>
        </div>
    );
}
