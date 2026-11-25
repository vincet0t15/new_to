import { CheckIcon, ChevronDownIcon, Search } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { cn } from '@/lib/utils';

export type ComboboxOption = {
    value: string;
    label: string;
    disabled?: boolean;
};

type ComboboxProps = {
    value?: string;
    onValueChange: (value: string) => void;
    options: ComboboxOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    className?: string;
    buttonClassName?: string;
    contentClassName?: string;
    disabled?: boolean;
    id?: string;
};

export function Combobox({
    value,
    onValueChange,
    options,
    placeholder = 'Select an option',
    searchPlaceholder = 'Search...',
    emptyMessage = 'No results found.',
    className,
    buttonClassName,
    contentClassName,
    disabled = false,
    id,
}: ComboboxProps) {
    const containerRef = useRef<HTMLDivElement | null>(null);
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [dropUp, setDropUp] = useState(false);
    const [listMaxHeight, setListMaxHeight] = useState(288);
    const [positioned, setPositioned] = useState(false);

    const positionDropdown = useCallback(() => {
        const button = buttonRef.current;

        if (!button) {
            return;
        }

        const buttonRect = button.getBoundingClientRect();
        let topBoundary = 0;
        let bottomBoundary = window.innerHeight;

        // A dialog scrolls its own content, so its edges — not just the
        // viewport — decide where the dropdown can be drawn without clipping.
        for (
            let node: HTMLElement | null = button.parentElement;
            node;
            node = node.parentElement
        ) {
            const overflowY = getComputedStyle(node).overflowY;

            if (overflowY === 'auto' || overflowY === 'scroll' || overflowY === 'hidden') {
                const rect = node.getBoundingClientRect();
                topBoundary = Math.max(topBoundary, rect.top);
                bottomBoundary = Math.min(bottomBoundary, rect.bottom);
            }
        }

        const gap = 4;
        const chrome = 56; // Search row and borders above the option list.
        const minListHeight = 96;
        const spaceBelow = bottomBoundary - buttonRect.bottom - gap;
        const spaceAbove = buttonRect.top - topBoundary - gap;
        const up = spaceBelow < chrome + minListHeight && spaceAbove > spaceBelow;
        const available = up ? spaceAbove : spaceBelow;

        setDropUp(up);
        setListMaxHeight(
            Math.max(minListHeight, Math.min(288, available - chrome)),
        );
        setPositioned(true);
    }, []);

    useEffect(() => {
        if (!open) {
            setSearch('');
            setPositioned(false);
            return;
        }

        positionDropdown();

        // The dialog is still animating when the dropdown first mounts, so
        // remeasure once its final size is known.
        const frame = requestAnimationFrame(positionDropdown);
        const dialog = buttonRef.current?.closest('[role="dialog"]') ?? null;
        const observer =
            dialog && typeof ResizeObserver !== 'undefined'
                ? new ResizeObserver(positionDropdown)
                : null;

        dialog?.addEventListener('animationend', positionDropdown);
        if (dialog && observer) {
            observer.observe(dialog);
        }

        const handlePointerDown = (event: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        window.addEventListener('resize', positionDropdown);
        document.addEventListener('scroll', positionDropdown, true);
        document.addEventListener('mousedown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            cancelAnimationFrame(frame);
            dialog?.removeEventListener('animationend', positionDropdown);
            observer?.disconnect();
            window.removeEventListener('resize', positionDropdown);
            document.removeEventListener('scroll', positionDropdown, true);
            document.removeEventListener('mousedown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [open, positionDropdown]);

    const selectedOption = useMemo(
        () => options.find((option) => option.value === value),
        [options, value],
    );

    const filteredOptions = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (!query) {
            return options;
        }

        return options.filter((option) =>
            option.label.toLowerCase().includes(query),
        );
    }, [options, search]);

    const handleSelect = (nextValue: string) => {
        onValueChange(nextValue);
        setOpen(false);
        setSearch('');
    };

    return (
        <div ref={containerRef} className={cn('relative w-full', className)}>
            <button
                ref={buttonRef}
                id={id}
                type="button"
                disabled={disabled}
                aria-expanded={open}
                aria-haspopup="listbox"
                onClick={() => setOpen((previous) => !previous)}
                className={cn(
                    'border-input bg-background text-foreground flex w-full items-center justify-between gap-2 rounded-md border px-3 py-2 text-left text-sm shadow-xs transition-colors outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50',
                    buttonClassName,
                )}
            >
                <span
                    className={cn(
                        'truncate',
                        !selectedOption && 'text-muted-foreground',
                    )}
                >
                    {selectedOption ? selectedOption.label : placeholder}
                </span>
                <ChevronDownIcon className="size-4 shrink-0 opacity-50" />
            </button>

            {open && positioned && (
                <div
                    className={cn(
                        'bg-popover text-popover-foreground absolute z-50 w-full overflow-hidden rounded-md border shadow-md',
                        dropUp ? 'bottom-full mb-1' : 'top-full mt-1',
                        contentClassName,
                    )}
                >
                    <div className="border-b px-2 py-2">
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-2 size-4 -translate-y-1/2" />
                            <input
                                autoFocus
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder={searchPlaceholder}
                                className="border-input bg-background text-foreground placeholder:text-muted-foreground w-full rounded-md border py-1.5 pr-2 pl-8 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                        </div>
                    </div>

                    {filteredOptions.length > 0 ? (
                        <div
                            className="overflow-y-auto p-1"
                            style={{ maxHeight: listMaxHeight }}
                        >
                            {filteredOptions.map((option) => {
                                const isSelected = option.value === value;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        disabled={option.disabled}
                                        onClick={() => handleSelect(option.value)}
                                        className={cn(
                                            'hover:bg-accent hover:text-accent-foreground relative flex w-full cursor-pointer items-center justify-between rounded-sm px-2 py-2 text-left text-sm transition-colors',
                                            isSelected && 'bg-accent text-accent-foreground',
                                            option.disabled && 'cursor-not-allowed opacity-50',
                                        )}
                                    >
                                        <span>{option.label}</span>
                                        {isSelected && (
                                            <CheckIcon className="size-4 shrink-0" />
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="text-muted-foreground px-3 py-3 text-sm">
                            {emptyMessage}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
