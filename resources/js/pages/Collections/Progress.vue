<script setup lang="ts">
import {
    BookOpen,
    ListTree,
    Layers,
    TrendingUp,
    CheckCircle,
    Award,
} from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import CollectionLayout from '@/layouts/CollectionLayout.vue';
import StatCard from '@/components/collection/StatCard.vue';
import type { Collection, CollectionProgressStats, QuizAttempt } from '@/types';

defineProps<{
    collection: Collection;
    stats: CollectionProgressStats;
    recent_attempts: QuizAttempt[];
}>();

const { t } = useI18n();
</script>

<template>
    <CollectionLayout :title="t('tabs.progress')" :collection="collection">
        <div class="flex flex-col gap-6 overflow-y-auto">
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <StatCard
                    :label="t('stats.lessons_ready')"
                    :value="stats.lessons_ready"
                    :icon="BookOpen"
                />
                <StatCard
                    :label="t('stats.lessons_mastered')"
                    :value="stats.lessons_mastered"
                    :icon="CheckCircle"
                    :accent="stats.lessons_mastered > 0"
                />
                <StatCard
                    :label="t('stats.terms_learned')"
                    :value="stats.terms_learned"
                    :icon="ListTree"
                />
                <StatCard
                    :label="t('stats.terms_mastered')"
                    :value="stats.terms_mastered"
                    :icon="CheckCircle"
                    :accent="stats.terms_mastered > 0"
                />
                <StatCard
                    :label="t('stats.flashcards_reviewed')"
                    :value="stats.flashcards_reviewed"
                    :icon="Layers"
                />
                <StatCard
                    :label="t('stats.avg_quiz_score')"
                    :value="
                        stats.average_quiz_score !== null
                            ? `${stats.average_quiz_score}%`
                            : '—'
                    "
                    :icon="TrendingUp"
                    :accent="
                        stats.average_quiz_score !== null &&
                        stats.average_quiz_score >= 80
                    "
                />
            </div>

            <!-- Recent Quiz Attempts -->
            <div v-if="recent_attempts.length > 0" class="space-y-3">
                <h2 class="font-heading text-sm font-bold text-on-surface">
                    {{ t('progress.recent_attempts') }}
                </h2>
                <div class="space-y-2">
                    <div
                        v-for="attempt in recent_attempts"
                        :key="attempt.id"
                        class="flex items-center justify-between rounded-xl border border-outline-glass bg-surface-container-lowest p-3"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold',
                                    attempt.score >= 80
                                        ? 'bg-success/10 text-success'
                                        : attempt.score >= 50
                                          ? 'bg-primary/10 text-primary'
                                          : 'bg-error-red/10 text-error-red',
                                ]"
                            >
                                <Award :size="14" />
                            </div>
                            <div>
                                <p
                                    class="text-xs font-semibold text-on-surface"
                                >
                                    {{ t('quizzes.score') }}:
                                    {{ attempt.score }}%
                                </p>
                                <p class="text-[10px] text-on-surface-variant">
                                    {{ attempt.created_at }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Data -->
            <div
                v-if="stats.lessons_ready === 0 && recent_attempts.length === 0"
                class="flex flex-col items-center justify-center gap-3 py-12"
            >
                <TrendingUp
                    :size="24"
                    class="text-on-surface-variant opacity-50"
                />
                <p class="text-sm text-on-surface-variant">
                    {{ t('progress.no_data') }}
                </p>
            </div>
        </div>
    </CollectionLayout>
</template>
