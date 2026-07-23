import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function EmptyState({
    title,
    description,
    action,
    className,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/30 px-6 py-16 text-center',
                className,
            )}
        >
            <h2 className="text-base font-medium text-foreground">{title}</h2>
            {description ? (
                <p className="text-muted-foreground max-w-sm text-sm">
                    {description}
                </p>
            ) : null}
            {action}
        </div>
    );
}
