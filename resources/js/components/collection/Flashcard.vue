<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from '@/components/ui/Button.vue';
import type { Flashcard as FlashcardType } from '@/types';

const props = defineProps<{
    card: FlashcardType;
}>();

const emit = defineEmits<{
    reviewed: [difficulty: 'again' | 'hard' | 'easy'];
}>();

const { t } = useI18n();
const isFlipped = ref(false);
const submitting = ref(false);

function flip(): void {
    isFlipped.value = !isFlipped.value;
}

function review(difficulty: 'again' | 'hard' | 'easy'): void {
    submitting.value = true;
    router.post(
        `/collections/${props.card.collection_id}/flashcards/${props.card.id}/review`,
        { difficulty },
        {
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
                isFlipped.value = false;
                emit('reviewed', difficulty);
            },
        },
    );
}
</script>

<template>
    <div class="flex flex-col items-center gap-4">
        <!-- Card -->
        <div class="perspective-1000 w-96 cursor-pointer" @click="flip">
            <div
                class="preserve-3d relative h-80 w-96 transition-transform duration-500"
                :class="{ 'rotate-y-180': isFlipped }"
            >
                <!-- Front -->
                <div
                    class="backface-hidden absolute inset-0 flex flex-col items-center justify-center gap-3 rounded-2xl border border-outline-glass bg-surface-container-lowest p-8 text-center shadow-sm transition-shadow hover:shadow-md"
                >
                    <p
                        class="text-[10px] font-bold tracking-wider text-on-surface-variant uppercase"
                    >
                        {{ t('flashcards.front') }}
                    </p>
                    <p class="font-heading text-2xl font-bold text-on-surface">
                        {{ card.front }}
                    </p>
                    <p
                        class="mt-4 text-[10px] text-on-surface-variant opacity-60"
                    >
                        {{ t('flashcards.click_to_flip') }}
                    </p>
                </div>

                <!-- Back -->
                <div
                    class="backface-hidden rotate-y-180 absolute inset-0 flex flex-col items-center justify-center gap-3 rounded-2xl border border-outline-glass bg-surface-container-lowest p-8 text-center shadow-sm"
                >
                    <p
                        class="text-[10px] font-bold tracking-wider text-on-surface-variant uppercase"
                    >
                        {{ t('flashcards.back') }}
                    </p>
                    <p class="font-heading text-2xl font-bold text-primary">
                        {{ card.back }}
                    </p>
                    <p
                        v-if="card.example"
                        class="mt-2 text-xs italic text-on-surface-variant"
                    >
                        "{{ card.example }}"
                    </p>
                </div>
            </div>
        </div>

        <!-- Review Buttons (only when flipped) -->
        <div v-if="isFlipped" class="flex gap-2">
            <Button
                variant="ghost"
                class="border border-error-red/20 text-error-red"
                :disabled="submitting"
                @click="review('again')"
            >
                {{ t('flashcards.again') }}
            </Button>
            <Button
                variant="ghost"
                class="border border-outline-glass"
                :disabled="submitting"
                @click="review('hard')"
            >
                {{ t('flashcards.hard') }}
            </Button>
            <Button :disabled="submitting" @click="review('easy')">
                {{ t('flashcards.easy') }}
            </Button>
        </div>
        <div v-else class="flex gap-2">
            <Button variant="ghost" @click="flip">
                {{ t('flashcards.show_answer') }}
            </Button>
        </div>
    </div>
</template>
