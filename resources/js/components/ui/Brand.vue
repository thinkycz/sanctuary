<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useSharedProps } from '@/composables/useSharedProps';

withDefaults(
    defineProps<{
        href?: string;
        class?: string;
    }>(),
    {
        href: '/',
        class: '',
    },
);

const { app } = useSharedProps();
</script>

<template>
    <Link :href="href" :class="class">
        {{ app.name }}
    </Link>
</template>
