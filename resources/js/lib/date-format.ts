/**
 * Date-only values arrive as UTC midnight timestamps. Slicing the date part
 * before parsing keeps the calendar day from shifting in timezones behind UTC.
 */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value.slice(0, 10)}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
