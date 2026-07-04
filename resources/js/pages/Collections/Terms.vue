<script setup lang="ts">
import { Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import CollectionLayout from '@/layouts/CollectionLayout.vue';
import TermCard from '@/components/collection/TermCard.vue';
import type { Collection, Term } from '@/types';

const props = defineProps<{
    collection: Collection;
    terms: Term[];
    filters: { search: string; difficulty: string };
}>();

const { t } = useI18n();
const searchQuery = ref(props.filters.search);
const difficultyFilter = ref(props.filters.difficulty);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch([searchQuery, difficultyFilter], ([search, difficulty]) => {
    if (debounceTimer !== null) {
        clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(() => {
        router.get(
            `/collections/${props.collection.id}/terms`,
            {
                search: search || undefined,
                difficulty: difficulty || undefined,
            },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['terms', 'filters'],
            },
        );
    }, 300);
});

const difficultyOptions = [
    { value: '', label: t('terms.difficulty.all') },
    { value: 'unknown', label: t('terms.difficulty.unknown') },
    { value: 'learning', label: t('terms.difficulty.learning') },
    { value: 'mastered', label: t('terms.difficulty.mastered') },
];
</script>

<template>
    <CollectionLayout :title="collection.title" :collection="collection">
        <div class="flex flex-col gap-4 overflow-hidden">
            <!-- Filters -->
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <Search
                        :size="14"
                        class="absolute top-1/2 left-3 -translate-y-1/2 text-on-surface-variant opacity-60"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="t('terms.search_placeholder')"
                        class="w-full rounded-xl border border-outline-glass bg-surface-container-low py-2 pr-3 pl-9 text-xs text-on-surface focus:border-primary focus:outline-none"
                    />
                </div>
                <select
                    v-model="difficultyFilter"
                    class="rounded-xl border border-outline-glass bg-surface-container-low px-3 py-2 text-xs text-on-surface focus:border-primary focus:outline-none"
                >
                    <option
                        v-for="opt in difficultyOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
            </div>

            <!-- Terms List -->
            <div class="flex-1 overflow-y-auto">
                <div
                    v-if="terms.length > 0"
                    class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                >
                    <TermCard
                        v-for="item in terms"
                        :key="item.id"
                        :item="item"
                    />
                </div>
                <div
                    v-else
                    class="flex flex-col items-center justify-center gap-2 py-12"
                >
                    <p class="text-sm text-on-surface-variant">
                        {{ t('terms.empty') }}
                    </p>
                </div>
            </div>
        </div>
    </CollectionLayout>
</template>
