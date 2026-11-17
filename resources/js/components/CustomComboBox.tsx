import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';

type CustomComboBoxItem = { value: string; label: string };

interface CustomComboBoxProps {
    items: CustomComboBoxItem[];
    placeholder: string;
    value?: string | null;
    onSelect?: (value: string | null) => void;
    showClear?: boolean;
    disableFilter?: boolean;
    disabled?: boolean;
    id?: string;
}

export function CustomComboBox({
    items,
    placeholder,
    value,
    onSelect,
    showClear = false,
    disabled = false,
    id,
}: CustomComboBoxProps) {
    return (
        <div className="flex items-center gap-1">
            <Combobox
                id={id}
                options={items}
                value={value ?? undefined}
                placeholder={placeholder}
                disabled={disabled}
                onValueChange={(nextValue) => onSelect?.(nextValue || null)}
            />
            {showClear && value && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="shrink-0"
                    aria-label="Clear selection"
                    onClick={() => onSelect?.(null)}
                >
                    <X />
                </Button>
            )}
        </div>
    );
}
