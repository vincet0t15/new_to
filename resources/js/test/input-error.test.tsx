import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import InputError from '@/components/input-error';

describe('InputError', () => {
    it('announces the message and associates it with the preceding input', () => {
        render(
            <div>
                <label htmlFor="name">Name</label>
                <input id="name" />
                <InputError message="Name is required." />
            </div>,
        );

        const message = screen.getByRole('alert');
        const input = screen.getByLabelText('Name');

        expect(message).toHaveTextContent('Name is required.');
        expect(input).toHaveAttribute('aria-invalid', 'true');
        expect(input).toHaveAttribute('aria-describedby', message.id);
    });

    it('finds the trigger inside a preceding combobox wrapper', () => {
        render(
            <div>
                <div>
                    <button type="button">All offices</button>
                </div>
                <InputError message="Choose an office." />
            </div>,
        );

        const trigger = screen.getByRole('button', { name: 'All offices' });

        expect(trigger).toHaveAttribute('aria-invalid', 'true');
        expect(trigger.getAttribute('aria-describedby')).toBeTruthy();
    });

    it('does not render when there is no message', () => {
        const { container } = render(<InputError message={undefined} />);

        expect(container).toBeEmptyDOMElement();
    });
});
