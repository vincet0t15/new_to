import { Check } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { OfficeSelectOption } from '@/types/office';

type OfficePickerProps = {
    offices: OfficeSelectOption[];
    value: number[];
    onChange: (officeIds: number[]) => void;
    hint?: string;
    error?: string;
    disabled?: boolean;
};

/**
 * Choosing the offices that print a signatory group.
 *
 * A visible list of checkboxes rather than a chip multi-select inside a
 * dropdown. The offices an assignment covers normally sit next to each other
 * in the sorted list, and a dropdown closes under the pointer in the middle of
 * that pass. The search filters what is shown and leaves the selection alone,
 * so finding one office does not quietly clear the others.
 */
export function OfficePicker({
    offices,
    value,
    onChange,
    hint,
    error,
    disabled = false,
}: OfficePickerProps) {
    const [search, setSearch] = useState('');

    const selected = useMemo(() => new Set(value), [value]);

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (term === '') {
            return offices;
        }

        return offices.filter((office) =>
            office.office_name.toLowerCase().includes(term),
        );
    }, [offices, search]);

    const toggle = (officeId: number, checked: boolean) => {
        onChange(
            checked
                ? [...value, officeId]
                : value.filter((current) => current !== officeId),
        );
    };

    /**
     * Every office currently listed, not the whole list.
     *
     * Ticking all while a search is on means the offices being looked at, which
     * is what the label on the box says at that moment.
     */
    const toggleAllVisible = (checked: boolean) => {
        if (!checked) {
            const visible = new Set(filtered.map((office) => office.id));
            onChange(value.filter((current) => !visible.has(current)));

            return;
        }

        onChange(
            Array.from(
                new Set([...value, ...filtered.map((office) => office.id)]),
            ),
        );
    };

    const allVisibleSelected =
        filtered.length > 0 &&
        filtered.every((office) => selected.has(office.id));

    return (
        <div className="space-y-2">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <Label>Offices</Label>
                <span className="text-xs text-muted-foreground tabular-nums">
                    {value.length === 0
                        ? 'No offices assigned'
                        : `${value.length} of ${offices.length} assigned`}
                </span>
            </div>

            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}

            <div className="rounded-md border">
                {offices.length > 6 && (
                    <div className="border-b p-2">
                        <Input
                            placeholder="Search offices..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="h-8"
                            disabled={disabled}
                        />
                    </div>
                )}

                <div className="max-h-56 overflow-y-auto p-1">
                    {filtered.length > 0 ? (
                        <>
                            <label className="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm font-medium hover:bg-muted/50">
                                <Checkbox
                                    checked={allVisibleSelected}
                                    disabled={disabled}
                                    onCheckedChange={(checked) =>
                                        toggleAllVisible(checked === true)
                                    }
                                />
                                <span className="flex-1">
                                    {allVisibleSelected
                                        ? 'Clear all shown'
                                        : 'Select all shown'}
                                </span>
                                <span className="text-xs text-muted-foreground tabular-nums">
                                    {filtered.length}
                                </span>
                            </label>

                            <div className="my-1 border-t" />

                            {filtered.map((office) => (
                                <label
                                    key={office.id}
                                    className={cn(
                                        'flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-muted/50',
                                        disabled &&
                                            'cursor-not-allowed opacity-60',
                                    )}
                                >
                                    <Checkbox
                                        checked={selected.has(office.id)}
                                        disabled={disabled}
                                        onCheckedChange={(checked) =>
                                            toggle(office.id, checked === true)
                                        }
                                    />
                                    <span className="flex-1 truncate">
                                        {office.office_name}
                                    </span>
                                    {selected.has(office.id) && (
                                        <Check className="size-3.5 shrink-0 opacity-60" />
                                    )}
                                </label>
                            ))}
                        </>
                    ) : (
                        <p className="px-2 py-4 text-center text-sm text-muted-foreground">
                            No offices found.
                        </p>
                    )}
                </div>
            </div>

            <InputError message={error} />
        </div>
    );
}
