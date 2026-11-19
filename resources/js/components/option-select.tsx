import { useMemo } from 'react';
import { CustomComboBox } from '@/components/CustomComboBox';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import type { SelectOption } from '@/types/ui';
import type { ReactNode } from 'react';

type OptionSelectProps = {
    id: string;
    label: ReactNode;
    options: SelectOption[];
    value: number | '' | null | undefined;
    onChange: (value: number | '') => void;
    placeholder: string;
    error?: string;
    allowEmpty?: boolean;
    disabled?: boolean;
};

/**
 * A labelled single-value picker over a plain id/name list.
 *
 * Used by the signatory group forms, where a signature line may name an
 * employee or be left blank, so the empty choice has to be reachable.
 */
export function OptionSelect({
    id,
    label,
    options,
    value,
    onChange,
    placeholder,
    error,
    allowEmpty = false,
    disabled = false,
}: OptionSelectProps) {
    const items = useMemo(
        () =>
            options.map((option) => ({
                value: String(option.id),
                label: option.name,
            })),
        [options],
    );

    const isEmpty = value === '' || value === null || value === undefined;

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            <CustomComboBox
                id={id}
                items={items}
                value={isEmpty ? null : String(value)}
                placeholder={placeholder}
                showClear={allowEmpty}
                disabled={disabled}
                onSelect={(next) => onChange(next === null ? '' : Number(next))}
            />
            <InputError message={error} />
        </div>
    );
}
