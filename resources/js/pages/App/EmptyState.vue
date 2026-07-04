<script setup lang="ts">
import { Plus, BookOpen, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import AddCollectionModal from '@/components/collection/AddCollectionModal.vue';
import { useSharedProps } from '@/composables/useSharedProps';

const { t } = useI18n();
const { collections } = useSharedProps();

const showAddModal = ref(false);
</script>

<template>
    <AppLayout :title="t('app.empty_title')">
        <div
            class="flex flex-1 flex-col items-center justify-center gap-8 text-center"
        >
            <div class="flex flex-col items-center gap-4">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl border border-primary/20 bg-primary/5"
                >
                    <Sparkles :size="28" class="text-primary" />
                </div>
                <div class="space-y-2">
                    <h1 class="font-heading text-2xl font-bold text-on-surface">
                        {{ t('app.welcome') }}
                    </h1>
                    <p class="max-w-md text-sm text-on-surface-variant">
                        {{ t('app.welcome_description') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-center gap-3">
                <button
                    class="flex cursor-pointer items-center gap-2 rounded-xl border border-primary/20 bg-gradient-to-b from-primary-container to-primary px-6 py-3 text-sm font-semibold text-white shadow-[0_4px_12px_rgba(13,148,136,0.2)] transition hover:brightness-105 active:scale-[0.98]"
                    @click="showAddModal = true"
                >
                    <Plus :size="16" />
                    {{ t('collections.create_first') }}
                </button>
            </div>

            <div
                v-if="collections.length === 0"
                class="grid grid-cols-1 gap-3 sm:grid-cols-3 max-w-2xl"
            >
                <div
                    class="flex flex-col items-center gap-2 rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                >
                    <BookOpen :size="20" class="text-primary" />
                    <p class="text-xs font-semibold text-on-surface">
                        {{ t('app.feature_collections') }}
                    </p>
                    <p class="text-[10px] text-on-surface-variant">
                        {{ t('app.feature_collections_desc') }}
                    </p>
                </div>
                <div
                    class="flex flex-col items-center gap-2 rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                >
                    <Sparkles :size="20" class="text-primary" />
                    <p class="text-xs font-semibold text-on-surface">
                        {{ t('app.feature_ai') }}
                    </p>
                    <p class="text-[10px] text-on-surface-variant">
                        {{ t('app.feature_ai_desc') }}
                    </p>
                </div>
                <div
                    class="flex flex-col items-center gap-2 rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                >
                    <Plus :size="20" class="text-primary" />
                    <p class="text-xs font-semibold text-on-surface">
                        {{ t('app.feature_learn') }}
                    </p>
                    <p class="text-[10px] text-on-surface-variant">
                        {{ t('app.feature_learn_desc') }}
                    </p>
                </div>
            </div>
        </div>

        <AddCollectionModal
            :open="showAddModal"
            @close="showAddModal = false"
        />
    </AppLayout>
</template>
