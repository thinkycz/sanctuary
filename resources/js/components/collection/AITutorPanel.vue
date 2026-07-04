<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from '@/components/ui/Button.vue';
import type { TutorMessage } from '@/types';

const props = defineProps<{
    messages: TutorMessage[];
    action: string;
}>();

const { t } = useI18n();
const input = ref('');
const sending = ref(false);
const messagesContainer = ref<HTMLElement | null>(null);

const suggestedPrompts = [
    t('tutor.prompts.explain_concept'),
    t('tutor.prompts.give_example'),
    t('tutor.prompts.practice'),
];

async function sendMessage(content?: string): Promise<void> {
    const message = (content ?? input.value).trim();
    if (!message || sending.value) return;

    sending.value = true;
    input.value = '';

    router.post(
        props.action,
        { content: message },
        {
            preserveScroll: true,
            onFinish: async () => {
                sending.value = false;
                await nextTick();
                if (messagesContainer.value) {
                    messagesContainer.value.scrollTop =
                        messagesContainer.value.scrollHeight;
                }
            },
        },
    );
}

function usePrompt(prompt: string): void {
    void sendMessage(prompt);
}
</script>

<template>
    <div class="flex h-full flex-col gap-4">
        <!-- Messages -->
        <div
            ref="messagesContainer"
            class="flex-1 space-y-3 overflow-y-auto rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
        >
            <div
                v-if="messages.length === 0"
                class="flex h-full flex-col items-center justify-center gap-3 text-center"
            >
                <p class="text-sm text-on-surface-variant">
                    {{ t('tutor.empty') }}
                </p>
                <div class="flex flex-wrap justify-center gap-2">
                    <button
                        v-for="prompt in suggestedPrompts"
                        :key="prompt"
                        type="button"
                        class="cursor-pointer rounded-lg border border-outline-glass bg-surface-container-low px-3 py-1.5 text-[10px] font-semibold text-on-surface-variant transition hover:border-primary/30 hover:text-primary"
                        @click="usePrompt(prompt)"
                    >
                        {{ prompt }}
                    </button>
                </div>
            </div>
            <div
                v-for="message in messages"
                :key="message.id"
                :class="[
                    'flex flex-col gap-1',
                    message.role === 'user' ? 'items-end' : 'items-start',
                ]"
            >
                <div
                    :class="[
                        'max-w-[80%] rounded-2xl px-4 py-2.5 text-sm',
                        message.role === 'user'
                            ? 'bg-primary text-white'
                            : 'bg-surface-container text-on-surface',
                    ]"
                >
                    {{ message.content }}
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="flex gap-2">
            <input
                v-model="input"
                type="text"
                :placeholder="t('tutor.input_placeholder')"
                :disabled="sending"
                class="flex-1 rounded-xl border border-outline-glass bg-surface-container-low px-4 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant focus:border-primary focus:outline-none"
                @keydown.enter="sendMessage()"
            />
            <Button :disabled="sending || !input.trim()" @click="sendMessage()">
                {{ t('tutor.send') }}
            </Button>
        </div>
    </div>
</template>
