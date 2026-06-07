<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, UserRound } from '@lucide/vue';
import Brand from '@/components/ui/Brand.vue';
import Button from '@/components/ui/Button.vue';
import FlashAlerts from '@/components/ui/FlashAlerts.vue';
import { useSharedProps } from '@/composables/useSharedProps';

defineProps<{
    title: string;
}>();

const { auth } = useSharedProps();

function logout(): void {
    router.post('/logout');
}
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4"
            >
                <Brand
                    href="/dashboard"
                    class="text-base font-semibold text-gray-950"
                />

                <nav class="flex items-center gap-2">
                    <Link
                        href="/dashboard"
                        class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Dashboard
                    </Link>
                    <Link
                        href="/settings/profile"
                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <UserRound class="size-4" />
                        Profile
                    </Link>
                    <Button variant="ghost" class="gap-2" @click="logout">
                        <LogOut class="size-4" />
                        Log out
                    </Button>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-950">
                        {{ title }}
                    </h1>
                    <p v-if="auth.user" class="mt-1 text-sm text-gray-600">
                        {{ auth.user.email }}
                    </p>
                </div>
                <Settings class="size-5 text-gray-500" />
            </div>

            <FlashAlerts />

            <slot />
        </main>
    </div>
</template>
