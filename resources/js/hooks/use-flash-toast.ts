import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

type FlashPayload = {
    toast?: FlashToast;
    success?: string;
    warning?: string;
    error?: string;
};

function showFlash(flash?: FlashPayload): void {
    if (!flash) {
        return;
    }

    if (flash.toast) {
        toast[flash.toast.type](flash.toast.message);
    }
    if (flash.success) {
        toast.success(flash.success);
    }
    if (flash.warning) {
        toast.warning(flash.warning);
    }
    if (flash.error) {
        toast.error(flash.error);
    }
}

export function useFlashToast(): void {
    useEffect(() => {
        showFlash((router as unknown as { page?: { props?: { flash?: FlashPayload } } }).page?.props?.flash);

        return router.on('flash', (event) => {
            const detail = (event as CustomEvent).detail?.flash as FlashPayload | undefined;
            showFlash(detail);
        });
    }, []);
}
