<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string | number;
    icon?: Component;
    accent?: boolean;
}>();
</script>

<template>
    <div
        :class="[
            'flex flex-col gap-2 rounded-2xl border p-4 transition-all',
            accent
                ? 'border-primary/20 bg-primary/5'
                : 'border-outline-glass bg-surface-container-lowest',
        ]"
    >
        <div class="flex items-center gap-2">
            <component :is="icon" v-if="icon" :size="16" class="text-primary" />
            <p
                class="text-[10px] font-bold tracking-wider text-on-surface-variant uppercase"
            >
                {{ label }}
            </p>
        </div>
        <p class="font-heading text-2xl font-bold text-on-surface">
            {{ value }}
        </p>
    </div>
</template>
