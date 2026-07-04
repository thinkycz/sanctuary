<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CollectionLayout from '@/layouts/CollectionLayout.vue';
import AddLessonModal from '@/components/collection/AddLessonModal.vue';
import LessonCard from '@/components/collection/LessonCard.vue';
import type { Collection, LessonSummary } from '@/types';

defineProps<{
    collection: Collection;
    lessons: LessonSummary[];
}>();

const { t } = useI18n();
const showAddLessonModal = ref(false);
</script>

<template>
    <CollectionLayout :title="collection.title" :collection="collection">
        <div class="flex flex-col gap-4 overflow-y-auto">
            <div class="flex items-center justify-between">
                <h2 class="font-heading text-sm font-bold text-on-surface">
                    {{ t('lessons.label') }} ({{ lessons.length }})
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
                <p class="text-sm text-on-surface-variant">
                    {{ t('lessons.empty') }}
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
