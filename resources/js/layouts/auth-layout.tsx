import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import type { AuthLayoutProps } from '@/types';

export default function AuthLayout({
    title = '',
    description = '',
    wide = false,
    children,
}: AuthLayoutProps) {
    return (
        <AuthLayoutTemplate title={title} description={description} wide={wide}>
            {children}
        </AuthLayoutTemplate>
    );
}
