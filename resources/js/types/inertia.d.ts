export type UserRank = 'restricted' | 'regular' | 'power' | 'moderator' | 'administrator';

export interface AuthUser {
    username: string;
    display_name: string;
    email: string | null;
    email_verified: boolean;
    rank: UserRank;
    locale: string | null;
    default_safety_filter: SafetyRating;
    default_visibility: Visibility | null;
}

export type Visibility = 'public' | 'authenticated' | 'unlisted' | 'private';
export type SafetyRating = 'safe' | 'sketchy' | 'unsafe';

/** The browse filters that actually applied, echoed back by the server. */
export interface MediaFilters {
    safety: SafetyRating[];
    untagged: boolean;
    unlisted: boolean;
}

export interface MediaCardData {
    hash_id: string;
    title: string | null;
    width: number;
    height: number;
    dominant_color: string | null;
    safety_rating: SafetyRating;
    has_thumbnail: boolean;
    /** Human-applied tags only; an untagged item is flagged in the grid. */
    tag_count: number;
}

export interface TagSummary {
    name: string;
    category: string | null;
    color: string | null;
    usage_count: number;
}

export interface TagCategorySummary {
    name: string;
    color: string;
    tags_count: number;
}

export type TagSource = 'human' | 'implied' | 'ai';

export interface TagOnMedia extends TagSummary {
    source: TagSource;
}

export interface TagSuggestion extends TagSummary {
    /** The string that matched — the tag's own name, or one of its aliases. */
    matched: string;
    description: string | null;
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
    tags: TagOnMedia[];
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

export interface SettingsSection {
    key: string;
    label: string;
    href: string;
}

export interface AdminUser {
    username: string;
    display_name: string;
    email: string | null;
    rank: UserRank;
    is_banned: boolean;
    ban_reason: string | null;
    registered_at: string | null;
    uploads: number;
}

export interface AccountStats {
    registered_at: string | null;
    last_seen_at: string | null;
    rank: UserRank;
    uploads: number;
    /** Placeholders — no feature behind them yet. */
    favorites: number;
    comments: number;
    liked: number;
    disliked: number;
}

export interface SharedProps {
    auth: {
        user: AuthUser | null;
        can: Record<string, boolean>;
    };
    /** The site name, editable in Settings → System. */
    app_name: string;
    locale: string;
    locales: string[];
    /** The rating vocabulary, in order — never hardcoded on the client. */
    safety_ratings: SafetyRating[];
    translations: Record<string, string>;
    flash: {
        success: string | null;
        error: string | null;
    };
}

declare module '@inertiajs/core' {
    interface PageProps extends SharedProps {}
}
