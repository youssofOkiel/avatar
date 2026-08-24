import { toast } from 'sonner';

type FormErrors = Record<string, string | string[] | undefined>;

function firstErrorMessage(errors: FormErrors): string | null {
    for (const value of Object.values(errors)) {
        if (typeof value === 'string' && value !== '') {
            return value;
        }

        if (Array.isArray(value) && value[0]) {
            return value[0];
        }
    }

    return null;
}

/**
 * Show an error toast and scroll the first invalid field/row into view.
 */
export function reportFormErrors(errors: FormErrors): void {
    const message = firstErrorMessage(errors);

    if (message) {
        toast.error(message);
    }

    requestAnimationFrame(() => {
        const target =
            document.querySelector<HTMLElement>('[data-invalid="true"]') ??
            document.querySelector<HTMLElement>('.border-destructive') ??
            document.querySelector<HTMLElement>('.text-destructive');

        target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
}
