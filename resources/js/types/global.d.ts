import type { Auth } from '@/types/auth';
import type { PortalOption } from '@/types/portal';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            portals: PortalOption[];
            currentPortal: string | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
