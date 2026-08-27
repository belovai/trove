export type UserRank = 'restricted' | 'regular' | 'power' | 'moderator' | 'administrator';

export interface AuthUser {
    username: string;
    display_name: string;
    rank: UserRank;
    locale: string | null;
    default_safety_filter: string;
}

export type Visibility = 'public' | 'authenticated' | 'unlisted' | 'private';
export type SafetyRating = 'safe' | 'sketchy' | 'unsafe';

export interface MediaCardData {
    hash_id: string;
    title: string | null;
    width: number;
    height: number;
    dominant_color: string | null;
    safety_rating: SafetyRating;
    has_thumbnail: boolean;
}

export interface MediaDetail extends MediaCardData {
    description: string | null;
    source: string | null;
    filesize: number;
    mime_type: string;
    is_animated: boolean;
    frame_count: number | null;
    visibility: Visibility;
    is_anonymous: boolean;
    uploader: string | null;
    created_at: string | null;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface DuplicateMatch {
    hash_id: string;
    title: string | null;
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
