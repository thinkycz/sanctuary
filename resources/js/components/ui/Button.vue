<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        type?: 'button' | 'submit';
        class?: string;
        disabled?: boolean;
    }>(),
    {
        type: 'button',
        class: '',
        disabled: false,
    },
);
</script>

<template>
    <button
        :type="props.type"
        :disabled="props.disabled"
        :class="
            cn(
                'inline-flex h-10 items-center justify-center rounded-xl border border-primary/20 bg-gradient-to-b from-primary-container to-primary px-4 text-xs font-semibold text-white shadow-[0_4px_12px_rgba(0,104,95,0.15)] transition hover:brightness-105 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer gap-2',
                props.class,
            )
        "
    >
        <slot />
    </button>
</template>
