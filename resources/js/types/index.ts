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

/**
 * A single chat message as serialized by `ConversationRepository`.
 *
 * `role` is intentionally a string (not a narrow union) because the
 * AI SDK may emit values such as `system` or `tool` that the frontend
 * does not branch on. `content` is nullable because persisted rows
 * may carry a `null` content (e.g. tool-call placeholders).
 */
export interface ChatMessage {
    role: string;
    content: string | null;
}

/**
 * The conversation payload rendered on the dashboard.
 */
export interface ChatConversation {
    id: string;
    title: string;
    messages: ChatMessage[];
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
