<script setup lang="ts">
import { CheckCircle2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from '@/components/ui/Button.vue';
import CollectionLayout from '@/layouts/CollectionLayout.vue';
import Flashcard from '@/components/collection/Flashcard.vue';
import type { Collection, Flashcard as FlashcardType } from '@/types';

const props = defineProps<{
    collection: Collection;
    flashcards: FlashcardType[];
}>();

const { t } = useI18n();
const currentIndex = ref(0);
const reviewedCount = ref(0);

const currentCard = computed<FlashcardType | null>(() => {
    return props.flashcards[currentIndex.value] ?? null;
});

const isComplete = computed(
    () =>
        props.flashcards.length > 0 &&
        currentIndex.value >= props.flashcards.length,
);

function onReviewed(difficulty: 'again' | 'hard' | 'easy'): void {
    if (difficulty === 'again') {
        return;
    }

    reviewedCount.value++;

    if (currentIndex.value < props.flashcards.length) {
        currentIndex.value++;
    }
}

function restart(): void {
    currentIndex.value = 0;
    reviewedCount.value = 0;
}
</script>

<template>
    <CollectionLayout :title="collection.title" :collection="collection">
        <div class="flex flex-1 flex-col items-center gap-6 overflow-hidden">
            <!-- Completion Screen -->
            <div
                v-if="isComplete"
                class="flex flex-1 flex-col items-center justify-center gap-4"
            >
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl border border-success/20 bg-success/5"
                >
                    <CheckCircle2 :size="24" class="text-success" />
                </div>
                <h2 class="font-heading text-lg font-bold text-on-surface">
                    {{ t('flashcards.complete') }}
                </h2>
                <p class="text-sm text-on-surface-variant">
                    {{
                        t('flashcards.complete_summary', {
                            count: reviewedCount,
                        })
                    }}
                </p>
                <Button @click="restart">
                    {{ t('flashcards.restart') }}
                </Button>
            </div>

            <!-- Flashcard Review -->
            <div
                v-else-if="flashcards.length > 0"
                class="flex w-full flex-1 flex-col items-center gap-4"
            >
                <!-- Progress -->
                <div
                    class="flex w-96 items-center justify-between text-xs text-on-surface-variant"
                >
                    <span
                        >{{ currentIndex + 1 }} / {{ flashcards.length }}</span
                    >
                    <div
                        class="mx-4 h-1 flex-1 rounded-full bg-surface-container"
                    >
                        <div
                            class="h-1 rounded-full bg-primary transition-all"
                            :style="{
                                width: `${((currentIndex + 1) / flashcards.length) * 100}%`,
                            }"
                        ></div>
                    </div>
                </div>

                <!-- Flashcard -->
                <div
                    v-if="currentCard"
                    class="flex flex-1 w-full items-center justify-center"
                >
                    <Flashcard :card="currentCard" @reviewed="onReviewed" />
                </div>
            </div>

            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center gap-3"
            >
                <p class="text-sm text-on-surface-variant">
                    {{ t('flashcards.empty') }}
                </p>
            </div>
        </div>
    </CollectionLayout>
</template>
