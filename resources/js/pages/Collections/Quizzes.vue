<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { HelpCircle } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import CollectionLayout from '@/layouts/CollectionLayout.vue';
import type { Collection, QuizSummary } from '@/types';

defineProps<{
    collection: Collection;
    quizzes: QuizSummary[];
}>();

const { t } = useI18n();
</script>

<template>
    <CollectionLayout :title="collection.title" :collection="collection">
        <div class="flex flex-col gap-4 overflow-y-auto">
            <h2 class="font-heading text-sm font-bold text-on-surface">
                {{ t('quizzes.label') }} ({{ quizzes.length }})
            </h2>

            <div
                v-if="quizzes.length > 0"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2"
            >
                <div
                    v-for="quiz in quizzes"
                    :key="quiz.id"
                    class="group flex cursor-pointer flex-col gap-2 rounded-2xl border border-outline-glass bg-surface-container-lowest p-4 transition-all hover:border-primary/30 hover:shadow-md"
                    @click="
                        router.visit(
                            `/collections/${collection.id}/quizzes/${quiz.id}`,
                        )
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <h3
                            class="font-heading text-sm font-bold text-on-surface group-hover:text-primary"
                        >
                            {{ quiz.title }}
                        </h3>
                        <div
                            :class="[
                                'flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold',
                                quiz.status === 'completed'
                                    ? 'bg-success/10 text-success'
                                    : 'bg-surface-container text-on-surface-variant',
                            ]"
                        >
                            {{ t(`quizzes.status.${quiz.status}`) }}
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 text-[10px] text-on-surface-variant"
                    >
                        <span class="flex items-center gap-1">
                            <HelpCircle :size="10" />
                            {{ quiz.total_questions }}
                            {{ t('quizzes.questions') }}
                        </span>
                        <span
                            v-if="quiz.score !== null"
                            class="font-bold text-primary"
                        >
                            {{ quiz.score }}%
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-outline-glass py-12"
            >
                <HelpCircle
                    :size="24"
                    class="text-on-surface-variant opacity-50"
                />
                <p class="text-sm text-on-surface-variant">
                    {{ t('quizzes.empty') }}
                </p>
            </div>
        </div>
    </CollectionLayout>
</template>
