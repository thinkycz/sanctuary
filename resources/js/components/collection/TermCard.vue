<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Term } from '@/types';

const props = defineProps<{
    item: Term;
}>();

const { t } = useI18n();
const submitting = ref(false);

const difficultyOptions = computed(() => [
    {
        value: 'unknown',
        label: t('terms.difficulty.unknown'),
        active: props.item.difficulty === 'unknown',
    },
    {
        value: 'learning',
        label: t('terms.difficulty.learning'),
        active: props.item.difficulty === 'learning',
    },
    {
        value: 'mastered',
        label: t('terms.difficulty.mastered'),
        active: props.item.difficulty === 'mastered',
    },
]);

function setDifficulty(value: string): void {
    submitting.value = true;
    router.put(
        `/collections/${props.item.collection_id}/terms/${props.item.id}`,
        { difficulty: value },
        {
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>

<template>
    <div
        class="flex flex-col gap-2 rounded-2xl border border-outline-glass bg-surface-container-lowest p-4 transition-all hover:shadow-sm"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex items-baseline gap-2">
                    <h3
                        class="font-heading text-base font-bold text-on-surface"
                    >
                        {{ item.term }}
                    </h3>
                </div>
                <p class="text-sm text-on-surface-variant">
                    {{ item.definition }}
                </p>
            </div>
            <span
                v-if="item.category"
                class="shrink-0 rounded-lg bg-surface-container px-2 py-0.5 text-[10px] font-semibold text-on-surface-variant"
            >
                {{ item.category }}
            </span>
        </div>

        <p v-if="item.example" class="text-xs italic text-on-surface-variant">
            "{{ item.example }}"
        </p>

        <div class="flex gap-1 pt-1">
            <button
                v-for="opt in difficultyOptions"
                :key="opt.value"
                type="button"
                :disabled="submitting"
                :class="[
                    'cursor-pointer rounded-lg px-2 py-1 text-[10px] font-semibold transition disabled:opacity-50',
                    opt.active
                        ? 'bg-primary text-white'
                        : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-low',
                ]"
                @click="setDifficulty(opt.value)"
            >
                {{ opt.label }}
            </button>
        </div>
    </div>
</template>
