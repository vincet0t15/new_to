export function formatMoney(value: number | string | null | undefined): string {
    const amount = Number(value ?? 0);

    if (Number.isNaN(amount)) {
        return '₱0.00';
    }

    return `₱${amount.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

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
