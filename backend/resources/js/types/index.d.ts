import '@inertiajs/core';

declare module '@inertiajs/core' {
    interface PageProps extends Record<string, unknown> {
        auth: {
            user: {
                id: number;
                name: string;
                email: string;
                account_role?: string | null;
                is_support_admin?: boolean;
            } | null;
        };
        csrf_token?: string;
        flash?: {
            success?: string;
            error?: string;
        };
    }
}
