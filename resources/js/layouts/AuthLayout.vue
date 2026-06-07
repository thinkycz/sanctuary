<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Brand from '@/components/ui/Brand.vue';
import FlashAlerts from '@/components/ui/FlashAlerts.vue';

defineProps<{
    title: string;
    subtitle: string;
}>();
</script>

<template>
    <Head :title="title" />

    <main
        class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-10"
    >
        <section class="w-full max-w-md">
            <Brand
                href="/login"
                class="mb-8 block text-center text-lg font-semibold text-gray-950"
            />

            <div
                class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-950">
                        {{ title }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">{{ subtitle }}</p>
                </div>

                <FlashAlerts />

                <slot />
            </div>
        </section>
    </main>
</template>
