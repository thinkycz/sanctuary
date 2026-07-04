<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-6 py-12">
        <div class="flex flex-col items-center gap-3">
            <div
                class="h-10 w-10 animate-pulse rounded-full bg-primary/20"
            ></div>
            <p class="text-sm font-semibold text-primary">
                {{ t('lessons.generating') }}
            </p>
            <p class="text-xs text-on-surface-variant">
                {{ t('lessons.generating_hint') }}
            </p>
        </div>

        <div class="w-full max-w-md space-y-3">
            <div class="h-4 animate-pulse rounded bg-surface-container"></div>
            <div
                class="h-4 w-3/4 animate-pulse rounded bg-surface-container"
            ></div>
            <div
                class="h-4 w-1/2 animate-pulse rounded bg-surface-container"
            ></div>
        </div>
    </div>
</template>
