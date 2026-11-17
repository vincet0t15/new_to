import { useMemo } from 'react';
import { CustomComboBox } from '@/components/CustomComboBox';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import type { ReactNode } from 'react';

type TravelSelectProps = {
    id: string;
    label?: ReactNode;
    options: { id: number | string; name: string }[];
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
    error?: string;
    allowEmpty?: boolean;
    disabled?: boolean;
};

/**
 * A string-valued select for the travel order forms. Every id in the payload
 * is posted as a string, so keeping the component string-based avoids casting
 * at every call site.
 */
export function TravelSelect({
    id,
    label,
    options,
    value,
    onChange,
    placeholder,
    error,
    allowEmpty = false,
    disabled = false,
}: TravelSelectProps) {
    const items = useMemo(
        () =>
            options.map((option) => ({
                value: String(option.id),
                label: option.name,
            })),
        [options],
    );

    return (
        <div className="space-y-2">
            {label && <Label htmlFor={id}>{label}</Label>}
            <CustomComboBox
                id={id}
                items={items}
                value={value === '' ? null : value}
                placeholder={placeholder}
                showClear={allowEmpty}
                disabled={disabled}
                onSelect={(next) => onChange(next ?? '')}
            />
            <InputError message={error} />
        </div>
    );
}
