import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { Combobox } from '@/components/ui/combobox';

const options = [
    { value: 'a', label: 'Alpha' },
    { value: 'b', label: 'Beta' },
];

describe('Combobox', () => {
    it('associates a labelled listbox and supports keyboard selection', async () => {
        const user = userEvent.setup();
        const onValueChange = vi.fn();

        render(
            <>
                <label htmlFor="office">Office</label>
                <Combobox
                    id="office"
                    options={options}
                    placeholder="Select an office"
                    onValueChange={onValueChange}
                />
            </>,
        );

        const trigger = screen.getByRole('combobox', { name: 'Office' });

        expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await user.click(trigger);

        const listbox = await screen.findByRole('listbox', {
            name: 'Select an office',
        });
        const renderedOptions = within(listbox).getAllByRole('option');

        expect(renderedOptions).toHaveLength(2);
        expect(renderedOptions[0]).toHaveAttribute('aria-selected', 'false');

        const search = screen.getByRole('textbox', { name: 'Search...' });

        expect(search).toHaveAttribute('aria-controls', listbox.id);

        await user.keyboard('{ArrowDown}{Enter}');

        expect(onValueChange).toHaveBeenCalledWith('b');
    });

    it('uses an explicit label when there is no htmlFor association', async () => {
        const user = userEvent.setup();

        render(
            <Combobox
                ariaLabel="Filter by office"
                options={options}
                placeholder="All offices"
                onValueChange={() => {}}
            />,
        );

        const trigger = screen.getByRole('combobox', {
            name: 'Filter by office',
        });

        await user.click(trigger);

        expect(
            await screen.findByRole('listbox', { name: 'Filter by office' }),
        ).toBeTruthy();
        expect(
            screen.getByRole('textbox', { name: 'Filter by office search' }),
        ).toBeTruthy();
    });
});
