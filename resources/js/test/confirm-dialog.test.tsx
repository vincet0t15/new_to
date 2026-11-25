import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { ConfirmDialog } from '@/components/confirm-dialog';

describe('ConfirmDialog', () => {
    it('requires an explicit confirmation before running the action', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();
        const onOpenChange = vi.fn();

        render(
            <ConfirmDialog
                open
                onOpenChange={onOpenChange}
                title="Delete this travel order?"
                description="This cannot be undone."
                confirmLabel="Delete order"
                destructive
                onConfirm={onConfirm}
            />,
        );

        expect(screen.getByRole('dialog')).toBeInTheDocument();
        expect(screen.getByText('This cannot be undone.')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(onOpenChange).toHaveBeenCalledWith(false);
        expect(onConfirm).not.toHaveBeenCalled();

        await user.click(screen.getByRole('button', { name: 'Delete order' }));

        expect(onConfirm).toHaveBeenCalledTimes(1);
    });
});
