import { Check } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Destination = { id: number; name: string };

type DestinationsPickerProps = {
    destinations: Destination[];
    value: number[];
    onChange: (values: number[]) => void;
    error?: string;
    id?: string;
    disabled?: boolean;
};

/**
 * Choosing the places a per diem covers.
 *
 * A visible list of checkboxes rather than a chip multi-select inside a
 * dropdown. The list is picked from by eye, not typed, and a dropdown hides it
 * the moment it opens: the places an office covers sit next to each other in
 * the sorted list, so ticking them is a single pass down the page, and putting
 * that pass behind a control that closes under the pointer is what made the old
 * control tiring to use.
 *
 * Select all is the other half of the same idea. A configuration either covers
 * everywhere or covers one area, and an area is normally a cluster of
 * neighbouring places, so naming it place by place is the tedious version of
 * the one decision somebody actually made.
 *
 * The search is there for the case the flat list cannot cover, which is a long
 * list of places: it filters what is shown and leaves the selection alone, so
 * filtering down to find one place and ticking the rest does not quietly clear
 * the others.
 */
export function DestinationsPicker({
    destinations,
    value,
    onChange,
    error,
    id = 'location_ids',
    disabled = false,
}: DestinationsPickerProps) {
    const [search, setSearch] = useState('');

    const selected = useMemo(() => new Set(value), [value]);

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (term === '') {
            return destinations;
        }

        return destinations.filter((destination) =>
            destination.name.toLowerCase().includes(term),
        );
    }, [destinations, search]);

    const toggle = (destinationId: number, checked: boolean) => {
        onChange(
            checked
                ? [...value, destinationId]
                : value.filter((current) => current !== destinationId),
        );
    };

    /**
     * Every destination currently listed, not the whole list.
     *
     * Ticking all while a search is on means the places being looked at, which
     * is what the label on the box says at that moment.
     */
    const toggleAllVisible = (checked: boolean) => {
        if (!checked) {
            const visible = new Set(
                filtered.map((destination) => destination.id),
            );
            onChange(value.filter((current) => !visible.has(current)));

            return;
        }

        onChange(
            Array.from(
                new Set([
                    ...value,
                    ...filtered.map((destination) => destination.id),
                ]),
            ),
        );
    };

    const allVisibleSelected =
        filtered.length > 0 &&
        filtered.every((destination) => selected.has(destination.id));

    return (
        <div className="space-y-2">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <Label htmlFor={id}>Destinations</Label>
                <span className="text-xs text-muted-foreground tabular-nums">
                    {value.length === 0
                        ? 'None selected — covers every destination'
                        : `${value.length} of ${destinations.length} selected`}
                </span>
            </div>

            <div className="rounded-md border">
                {destinations.length > 6 && (
                    <div className="border-b p-2">
                        <Input
                            placeholder="Search destinations..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="h-8"
                            disabled={disabled}
                        />
                    </div>
                )}

                <div className="max-h-64 overflow-y-auto p-1">
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

                            {filtered.map((destination) => (
                                <label
                                    key={destination.id}
                                    className={cn(
                                        'flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-muted/50',
                                        disabled &&
                                            'cursor-not-allowed opacity-60',
                                    )}
                                >
                                    <Checkbox
                                        checked={selected.has(destination.id)}
                                        disabled={disabled}
                                        onCheckedChange={(checked) =>
                                            toggle(
                                                destination.id,
                                                checked === true,
                                            )
                                        }
                                    />
                                    <span className="flex-1 truncate">
                                        {destination.name}
                                    </span>
                                    {selected.has(destination.id) && (
                                        <Check className="size-3.5 shrink-0 opacity-60" />
                                    )}
                                </label>
                            ))}
                        </>
                    ) : (
                        <p className="px-2 py-4 text-center text-sm text-muted-foreground">
                            No destinations found.
                        </p>
                    )}
                </div>
            </div>

            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
