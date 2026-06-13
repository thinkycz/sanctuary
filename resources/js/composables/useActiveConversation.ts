import { ref } from 'vue';

/**
 * Module-level singleton ref that holds the ID of a conversation that is
 * actively being streamed (i.e. created just now and not yet persisted in the
 * Inertia page URL).  Dashboard.vue sets this as soon as it receives the
 * X-Conversation-ID response header and clears it when the stream finishes.
 * AppLayout.vue reads it to highlight the correct sidebar item immediately,
 * without waiting for a full Inertia navigation.
 */
const pendingConversationId = ref<string | null>(null);

export function useActiveConversation() {
    return { pendingConversationId };
}
