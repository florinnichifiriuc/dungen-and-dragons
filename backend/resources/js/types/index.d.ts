import '@inertiajs/core';

declare module '@inertiajs/core' {
    interface PageProps extends Record<string, unknown> {
        auth: {
            user: {
                id: number;
                name: string;
                email: string;
            } | null;
        };
        csrf_token?: string;
        flash?: {
            success?: string;
            error?: string;
        };
    }
}
