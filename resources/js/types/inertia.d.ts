export type UserRank = 'restricted' | 'regular' | 'power' | 'moderator' | 'administrator';

export interface AuthUser {
    username: string;
    display_name: string;
    rank: UserRank;
    locale: string | null;
    default_safety_filter: string;
}

export interface SharedProps {
    auth: {
        user: AuthUser | null;
        can: Record<string, boolean>;
    };
    locale: string;
    locales: string[];
    translations: Record<string, string>;
    flash: {
        success: string | null;
        error: string | null;
    };
}

declare module '@inertiajs/core' {
    interface PageProps extends SharedProps {}
}
