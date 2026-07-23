import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function AppLogoIcon({
    className,
    alt = 'Avatar Educational Center',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/logo.jpg"
            alt={alt}
            className={cn('object-contain', className)}
            {...props}
        />
    );
}
