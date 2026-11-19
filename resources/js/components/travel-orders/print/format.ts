/**
 * Print-specific formatting.
 *
 * Kept out of the shared date helpers because the printed documents have
 * their own conventions: a period reads as prose ("March 1-2, 2026") rather
 * than the compact form the on-screen tables use, and a name printed in a
 * signature block is always upper case.
 */

/**
 * Render a travel period the way it reads on the paper forms.
 *
 * Collapses the repeated parts of the range so a two-day trip inside one month
 * does not print the month and year twice.
 */
export function formatPeriod(from: string | null, to: string | null): string {
    if (!from || !to) {
        return '—';
    }

    const start = new Date(`${from.slice(0, 10)}T00:00:00`);
    const end = new Date(`${to.slice(0, 10)}T00:00:00`);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return '—';
    }

    if (from === to) {
        return start.toLocaleDateString('en-PH', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        });
    }

    const sameMonth =
        start.getMonth() === end.getMonth() &&
        start.getFullYear() === end.getFullYear();
    const sameYear = start.getFullYear() === end.getFullYear();

    if (sameMonth) {
        const month = start.toLocaleDateString('en-PH', { month: 'long' });

        return `${month} ${start.getDate()}-${end.getDate()}, ${start.getFullYear()}`;
    }

    if (sameYear) {
        const short = (date: Date) =>
            date.toLocaleDateString('en-PH', { month: 'long', day: 'numeric' });

        return `${short(start)} - ${short(end)}, ${start.getFullYear()}`;
    }

    const long = (date: Date) =>
        date.toLocaleDateString('en-PH', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        });

    return `${long(start)} - ${long(end)}`;
}

/**
 * A person's name as printed in a signature block.
 */
export function capName(name: string | null | undefined): string {
    return (name ?? '').toUpperCase();
}

/**
 * Join a list as prose: "A", "A and B", "A, B and C".
 */
export function listPhrase(items: string[]): string {
    if (items.length === 0) {
        return '—';
    }

    if (items.length === 1) {
        return items[0];
    }

    return `${items.slice(0, -1).join(', ')} and ${items[items.length - 1]}`;
}

/**
 * A date as it reads in a document header: "December 1, 2026".
 */
export function formatLongDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value.slice(0, 10)}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleDateString('en-PH', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

/**
 * A compact date for a table cell: "Dec 1".
 */
export function formatShortDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value.slice(0, 10)}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
    });
}

/**
 * An itinerary row's date span, collapsed when both ends are the same day.
 */
export function formatEntryDateRange(
    from: string | null,
    to: string | null,
): string {
    if (!from) {
        return '—';
    }

    const start = formatShortDate(from);

    if (!to || to === from) {
        return start;
    }

    return `${start} - ${formatShortDate(to)}`;
}

/**
 * An itinerary row's time, read from a plain HH:MM value.
 */
export function formatDisplayTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    const [hours, minutes] = value.split(':').map(Number);

    if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
        return value;
    }

    const date = new Date();
    date.setHours(hours, minutes, 0, 0);

    return date.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
}

/**
 * A peso amount as printed in an itinerary money column.
 */
export function formatMoney(value: string | number | null | undefined): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value ?? 0));
}
