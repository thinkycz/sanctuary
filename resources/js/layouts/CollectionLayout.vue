<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    BookOpen,
    ListTree,
    Layers,
    HelpCircle,
    MessageCircle,
    TrendingUp,
    Pencil,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import EditCollectionModal from '@/components/collection/EditCollectionModal.vue';
import { useSharedProps } from '@/composables/useSharedProps';
import type { Collection } from '@/types';

const props = defineProps<{
    title: string;
    collection: Collection;
}>();

const { activeUrl } = useSharedProps();
const { t } = useI18n();

const collectionId = computed(() => props.collection.id);
const editModalOpen = ref(false);

const tabs = computed(() => [
    {
        key: 'overview',
        label: t('tabs.overview'),
        icon: LayoutGrid,
        href: `/collections/${collectionId.value}`,
        active: activeUrl.value === `/collections/${collectionId.value}`,
    },
    {
        key: 'lessons',
        label: t('tabs.lessons'),
        icon: BookOpen,
        href: `/collections/${collectionId.value}/lessons`,
        active:
            activeUrl.value === `/collections/${collectionId.value}/lessons`,
    },
    {
        key: 'terms',
        label: t('tabs.terms'),
        icon: ListTree,
        href: `/collections/${collectionId.value}/terms`,
        active: activeUrl.value.startsWith(
            `/collections/${collectionId.value}/terms`,
        ),
    },
    {
        key: 'flashcards',
        label: t('tabs.flashcards'),
        icon: Layers,
        href: `/collections/${collectionId.value}/flashcards`,
        active: activeUrl.value.startsWith(
            `/collections/${collectionId.value}/flashcards`,
        ),
    },
    {
        key: 'quizzes',
        label: t('tabs.quizzes'),
        icon: HelpCircle,
        href: `/collections/${collectionId.value}/quizzes`,
        active: activeUrl.value.startsWith(
            `/collections/${collectionId.value}/quizzes`,
        ),
    },
    {
        key: 'tutor',
        label: t('tabs.tutor'),
        icon: MessageCircle,
        href: `/collections/${collectionId.value}/tutor`,
        active: activeUrl.value.startsWith(
            `/collections/${collectionId.value}/tutor`,
        ),
    },
    {
        key: 'progress',
        label: t('tabs.progress'),
        icon: TrendingUp,
        href: `/collections/${collectionId.value}/progress`,
        active: activeUrl.value.startsWith(
            `/collections/${collectionId.value}/progress`,
        ),
    },
]);
</script>

<template>
    <AppLayout :title="title">
        <!-- Collection Header -->
        <div class="mb-6 flex items-center gap-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-outline-glass bg-surface-container-low text-lg"
            >
                {{ collection.icon ?? '📚' }}
            </div>
            <div class="min-w-0 flex-1">
                <h1
                    class="truncate font-heading text-lg font-bold text-on-surface"
                >
                    {{ collection.title }}
                </h1>
                <p
                    v-if="collection.description"
                    class="truncate text-xs text-on-surface-variant"
                >
                    {{ collection.description }}
                </p>
            </div>
            <button
                type="button"
                class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-outline-glass text-on-surface-variant transition hover:bg-surface-container-low hover:text-on-surface"
                :title="t('collections.edit')"
                :aria-label="t('collections.edit')"
                @click="editModalOpen = true"
            >
                <Pencil :size="14" />
            </button>
        </div>

        <!-- Collection Tabs -->
        <nav
            class="mb-6 flex gap-1 overflow-x-auto border-b border-outline-glass"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.key"
                :href="tab.href"
                :class="[
                    'flex shrink-0 cursor-pointer items-center gap-1.5 border-b-2 px-3 py-2 text-xs font-semibold transition-all',
                    tab.active
                        ? 'border-primary text-primary'
                        : 'border-transparent text-on-surface-variant hover:text-on-surface',
                ]"
            >
                <component :is="tab.icon" :size="14" />
                {{ tab.label }}
            </Link>
        </nav>

        <!-- Tab Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <slot />
        </div>

        <!-- Edit Collection Modal -->
        <EditCollectionModal
            :open="editModalOpen"
            :collection="collection"
            @close="editModalOpen = false"
        />
    </AppLayout>
</template>
