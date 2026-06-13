export interface AuthUser {
    id: number;
    email: string;
    locale: string;
    email_verified_at: string | null;
}

export interface AppMeta {
    name: string;
    locale: string;
    locales: string[];
}

export interface FlashProps {
    success: string | null;
    error: string | null;
}

export interface ConversationItem {
    id: string;
    title: string;
    updated_at: string;
}

export interface SharedProps {
    [key: string]: unknown;

    app: AppMeta;
    auth: {
        user: AuthUser | null;
    };
    conversations: ConversationItem[];
    flash: FlashProps;
    errors: Record<string, string>;
}
