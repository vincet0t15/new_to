import { usePage } from '@inertiajs/react';

export type CanFn = (permission: string) => boolean;

export function useCan(): CanFn {
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];

    return (permission: string) => permissions.includes(permission);
}

export function useCanAny(): (permissions: string[]) => boolean {
    const can = useCan();

    return (permissions: string[]) =>
        permissions.some((permission) => can(permission));
}
