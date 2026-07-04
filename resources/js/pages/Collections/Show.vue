<script setup lang="ts">
import { Plus, BookOpen, ListTree, Layers, TrendingUp } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CollectionLayout from '@/layouts/CollectionLayout.vue';
import AddLessonModal from '@/components/collection/AddLessonModal.vue';
import LessonCard from '@/components/collection/LessonCard.vue';
import StatCard from '@/components/collection/StatCard.vue';
import type {
    Collection,
    CollectionOverviewStats,
    LessonSummary,
} from '@/types';

defineProps<{
    collection: Collection;
    lessons: LessonSummary[];
    stats: CollectionOverviewStats;
}>();

const { t } = useI18n();
const showAddLessonModal = ref(false);
</script>

<template>
    <CollectionLayout :title="collection.title" :collection="collection">
        <div class="flex flex-col gap-6 overflow-y-auto">
            <!-- Stats -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <StatCard
                    :label="t('stats.lessons_ready')"
                    :value="stats.lessons_ready"
                    :icon="BookOpen"
                />
                <StatCard
                    :label="t('stats.terms_learned')"
                    :value="stats.terms_learned"
                    :icon="ListTree"
                />
                <StatCard
                    :label="t('stats.flashcards_due')"
                    :value="stats.flashcards_due"
                    :icon="Layers"
                    :accent="stats.flashcards_due > 0"
                />
                <StatCard
                    :label="t('stats.avg_quiz_score')"
                    :value="
                        stats.average_quiz_score !== null
                            ? `${stats.average_quiz_score}%`
                            : '—'
                    "
                    :icon="TrendingUp"
                />
            </div>

            <!-- Lessons Section -->
            <div class="flex items-center justify-between">
                <h2 class="font-heading text-sm font-bold text-on-surface">
                    {{ t('collections.recent_lessons') }}
                </h2>
                <button
                    class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary/10"
                    @click="showAddLessonModal = true"
                >
                    <Plus :size="12" />
                    {{ t('lessons.new') }}
                </button>
            </div>

            <div
                v-if="lessons.length > 0"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2"
            >
                <LessonCard
                    v-for="lesson in lessons"
                    :key="lesson.id"
                    :lesson="lesson"
                />
            </div>
            <div
                v-else
                class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-outline-glass py-12"
            >
                <BookOpen
                    :size="24"
                    class="text-on-surface-variant opacity-50"
                />
                <p class="text-sm text-on-surface-variant">
                    {{ t('collections.no_lessons') }}
                </p>
                <button
                    class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary/10"
                    @click="showAddLessonModal = true"
                >
                    <Plus :size="12" />
                    {{ t('lessons.create_first') }}
                </button>
            </div>
        </div>

        <AddLessonModal
            :open="showAddLessonModal"
            :collection-id="collection.id"
            @close="showAddLessonModal = false"
        />
    </CollectionLayout>
</template>
