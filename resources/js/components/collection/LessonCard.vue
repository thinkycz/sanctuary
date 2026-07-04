<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, AlertCircle, CheckCircle, Loader } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { LessonSummary } from '@/types';

const props = defineProps<{
    lesson: LessonSummary;
}>();

const { t } = useI18n();

const statusInfo = computed(() => {
    switch (props.lesson.status) {
        case 'ready':
            return {
                icon: CheckCircle,
                color: 'text-success',
                bg: 'bg-success/10',
            };
        case 'generating':
        case 'pending':
            return { icon: Loader, color: 'text-primary', bg: 'bg-primary/10' };
        case 'failed':
            return {
                icon: AlertCircle,
                color: 'text-error-red',
                bg: 'bg-error-red/10',
            };
        default:
            return {
                icon: Clock,
                color: 'text-on-surface-variant',
                bg: 'bg-surface-container-low',
            };
    }
});

const difficultyLabel = computed(() => {
    const map: Record<string, string> = {
        beginner: t('lessons.difficulty.beginner'),
        intermediate: t('lessons.difficulty.intermediate'),
        advanced: t('lessons.difficulty.advanced'),
    };
    return map[props.lesson.difficulty] ?? props.lesson.difficulty;
});
</script>

<template>
    <Link
        :href="`/lessons/${lesson.id}`"
        class="group flex cursor-pointer flex-col gap-2 rounded-2xl border border-outline-glass bg-surface-container-lowest p-4 transition-all hover:border-primary/30 hover:shadow-md"
    >
        <div class="flex items-start justify-between gap-3">
            <h3
                class="font-heading text-sm font-bold text-on-surface group-hover:text-primary"
            >
                {{ lesson.title }}
            </h3>
            <div
                :class="[
                    'flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold',
                    statusInfo.bg,
                    statusInfo.color,
                ]"
            >
                <component
                    :is="statusInfo.icon"
                    :size="10"
                    :class="
                        lesson.status === 'generating' ? 'animate-spin' : ''
                    "
                />
                {{ t(`lessons.status.${lesson.status}`) }}
            </div>
        </div>

        <div
            class="flex items-center gap-3 text-[10px] text-on-surface-variant"
        >
            <span
                class="rounded-lg bg-surface-container px-2 py-0.5 font-semibold"
            >
                {{ difficultyLabel }}
            </span>
            <span v-if="lesson.progress_status !== 'new'" class="font-semibold">
                {{ t(`lessons.progress.${lesson.progress_status}`) }}
            </span>
        </div>

        <p v-if="lesson.error_message" class="text-[10px] text-error-red">
            {{ lesson.error_message }}
        </p>
    </Link>
</template>
