import { useEffect, useId, useRef, type HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

function nearestControl(error: HTMLElement): HTMLElement | null {
    let sibling = error.previousElementSibling;

    for (let depth = 0; sibling && depth < 3; depth += 1) {
        const control = sibling.matches('input, select, textarea')
            ? (sibling as HTMLElement)
            : sibling.querySelector<HTMLElement>(
                  'input, select, textarea, button',
              );

        if (control) {
            return control;
        }

        sibling = sibling.previousElementSibling;
    }

    return null;
}

export default function InputError({
    message,
    className = '',
    id,
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    const generatedId = useId();
    const errorId = id ?? generatedId;
    const ref = useRef<HTMLParagraphElement>(null);

    useEffect(() => {
        const error = ref.current;

        if (!error || !message) {
            return;
        }

        const control = nearestControl(error);

        if (!control) {
            return;
        }

        control.setAttribute('aria-invalid', 'true');
        const describedBy = control.getAttribute('aria-describedby');

        if (!describedBy?.split(/\s+/).includes(errorId)) {
            control.setAttribute(
                'aria-describedby',
                [describedBy, errorId].filter(Boolean).join(' '),
            );
        }

        return () => {
            control.removeAttribute('aria-invalid');
            const current = control.getAttribute('aria-describedby');

            if (current) {
                const remaining = current
                    .split(/\s+/)
                    .filter((token) => token !== errorId)
                    .join(' ');

                if (remaining) {
                    control.setAttribute('aria-describedby', remaining);
                } else {
                    control.removeAttribute('aria-describedby');
                }
            }
        };
    }, [message, errorId]);

    return message ? (
        <p
            {...props}
            ref={ref}
            id={errorId}
            role="alert"
            className={cn('text-sm text-red-600 dark:text-red-400', className)}
        >
            {message}
        </p>
    ) : null;
}
