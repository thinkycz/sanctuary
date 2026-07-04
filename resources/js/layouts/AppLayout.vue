<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Settings as SettingsIcon,
    LogOut,
    Plus,
    BookOpen,
    Search,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Brand from '@/components/ui/Brand.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import FlashAlerts from '@/components/ui/FlashAlerts.vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useSharedProps } from '@/composables/useSharedProps';

defineProps<{
    title: string;
}>();

const { auth, collections, activeUrl } = useSharedProps();
const { t } = useI18n();
const mobileSidebarOpen = ref(false);
const searchQuery = ref('');

useBoundLocale();

const activeCollectionId = computed(() => {
    const match = activeUrl.value.match(/^\/collections\/(\d+)/);
    return match ? parseInt(match[1], 10) : null;
});

const currentTab = computed(() => {
    if (activeUrl.value.startsWith('/settings')) return 'settings';
    if (activeUrl.value.startsWith('/app')) return 'app';
    return 'collection';
});

const filteredCollections = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return collections.value;
    return collections.value.filter((c) =>
        c.title.toLowerCase().includes(query),
    );
});

const userInitials = computed(() => {
    const email = auth.value.user?.email ?? '';
    if (!email) return '';
    return email.substring(0, 2).toUpperCase();
});

const userLabel = computed(() => {
    const user = auth.value.user;
    if (!user) return t('fields.user');
    return user.email.split('@')[0];
});

function logout(): void {
    router.post('/logout');
}

async function deleteCollection(id: number): Promise<void> {
    const confirmDialog = useConfirmDialog();

    if (await confirmDialog.confirm(t('collections.delete_confirm'))) {
        router.delete(`/collections/${id}`);
    }
}

function closeMobileSidebar(): void {
    mobileSidebarOpen.value = false;
}
</script>

<template>
    <Head :title="title" />

    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-primary focus:px-4 focus:py-2 focus:text-xs focus:font-bold focus:text-white"
    >
        {{ t('nav.skip_to_main') }}
    </a>

    <div
        class="flex h-screen flex-col overflow-hidden bg-surface-bg font-sans antialiased md:flex-row"
    >
        <!-- Desktop Persistent Sidebar -->
        <aside
            class="sticky top-0 z-20 hidden h-screen w-64 flex-col border-r border-outline-glass bg-surface-container px-4 py-6 text-left md:flex"
        >
            <!-- Brand App Header -->
            <div
                class="mb-6 flex cursor-default items-center gap-3 px-2 transition-all select-none"
            >
                <Brand href="/app" />
            </div>

            <!-- New Collection Button -->
            <Link
                href="/app"
                class="mb-4 flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-primary/20 bg-gradient-to-b from-primary-container to-primary px-4 py-2.5 text-xs font-semibold text-white shadow-[0_4px_12px_rgba(13,148,136,0.2)] transition hover:brightness-105 active:scale-[0.98]"
            >
                <Plus :size="14" />
                {{ t('collections.new') }}
            </Link>

            <!-- Search -->
            <div class="relative mb-4">
                <Search
                    :size="14"
                    class="absolute top-1/2 left-3 -translate-y-1/2 text-on-surface-variant opacity-60"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="t('collections.search')"
                    class="w-full rounded-xl border border-outline-glass bg-surface-container-low py-2 pr-3 pl-9 text-xs text-on-surface placeholder:text-on-surface-variant focus:border-primary focus:outline-none"
                />
            </div>

            <!-- Collections List -->
            <nav class="flex-1 space-y-1 overflow-y-auto">
                <p
                    class="mb-2 px-3 text-[10px] font-bold tracking-wider text-on-surface-variant uppercase opacity-75"
                >
                    {{ t('collections.label') }}
                </p>
                <div v-if="filteredCollections.length > 0" class="space-y-1">
                    <div
                        v-for="collection in filteredCollections"
                        :key="collection.id"
                        class="group relative flex w-full items-center justify-between rounded-xl px-3 py-2 text-xs font-semibold transition-all hover:bg-surface-container-low"
                        :class="[
                            activeCollectionId === collection.id
                                ? 'bg-surface-container-low border-r-2 border-primary font-bold text-primary'
                                : 'text-on-surface-variant',
                        ]"
                    >
                        <Link
                            :href="`/collections/${collection.id}`"
                            class="flex flex-1 items-center gap-3 truncate pr-6 text-left"
                        >
                            <span
                                v-if="collection.icon"
                                class="shrink-0 text-sm"
                                >{{ collection.icon }}</span
                            >
                            <BookOpen v-else :size="14" class="shrink-0" />
                            <span class="truncate">{{ collection.title }}</span>
                        </Link>
                        <button
                            @click.stop="deleteCollection(collection.id)"
                            class="absolute right-2 hidden cursor-pointer rounded-lg p-1 text-on-surface-variant hover:bg-error-red/10 hover:text-error-red group-hover:block"
                            :title="t('collections.delete')"
                        >
                            <Trash2 :size="12" />
                        </button>
                    </div>
                </div>
                <p
                    v-else
                    class="px-3 py-2 text-xs text-on-surface-variant opacity-70"
                >
                    {{
                        searchQuery
                            ? t('collections.no_search_results')
                            : t('collections.empty')
                    }}
                </p>
            </nav>

            <!-- Footer: User Identity + Quick Actions -->
            <div
                class="flex items-center justify-between gap-2 border-t border-outline-glass px-2 pt-4"
            >
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <div
                        aria-hidden="true"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-outline-glass bg-surface-container-low font-heading text-xs font-bold text-primary"
                    >
                        {{ userInitials }}
                    </div>
                    <div class="min-w-0 overflow-hidden">
                        <p
                            class="truncate text-xs font-semibold text-on-surface"
                        >
                            {{ userLabel }}
                        </p>
                        <p
                            class="truncate text-[9px] font-medium text-on-surface-variant opacity-85"
                        >
                            {{ auth.user ? auth.user.email : '' }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    <Link
                        href="/settings"
                        :class="[
                            'cursor-pointer rounded-lg p-1.5 transition-all',
                            currentTab === 'settings'
                                ? 'bg-surface-container-low text-primary'
                                : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary',
                        ]"
                        :title="t('nav.settings')"
                        :aria-label="t('nav.settings')"
                    >
                        <SettingsIcon :size="14" />
                    </Link>
                    <button
                        @click="logout"
                        class="cursor-pointer rounded-lg p-1.5 text-on-surface-variant transition-all hover:bg-error-red/10 hover:text-error-red"
                        :title="t('nav.logout')"
                        :aria-label="t('nav.logout')"
                    >
                        <LogOut :size="14" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile Top Navigation Header -->
        <header
            class="glass-panel sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-outline-glass px-4 shadow-sm md:hidden"
        >
            <div class="flex items-center gap-2">
                <Brand href="/app" />
            </div>

            <div class="flex items-center gap-1.5">
                <Link
                    href="/app"
                    class="rounded-lg p-2 text-on-surface-variant transition-all"
                    :title="t('collections.new')"
                    :aria-label="t('collections.new')"
                    @click="closeMobileSidebar"
                >
                    <Plus :size="16" />
                </Link>
                <button
                    type="button"
                    class="rounded-lg p-2 text-on-surface-variant transition-all"
                    :class="
                        mobileSidebarOpen
                            ? 'bg-surface-container-low text-primary'
                            : ''
                    "
                    :title="t('collections.label')"
                    :aria-label="t('collections.label')"
                    :aria-expanded="mobileSidebarOpen"
                    @click="mobileSidebarOpen = !mobileSidebarOpen"
                >
                    <BookOpen :size="16" />
                </button>
                <Link
                    href="/settings"
                    :class="[
                        'rounded-lg p-2 transition-all',
                        currentTab === 'settings'
                            ? 'font-bold text-primary bg-surface-container-low'
                            : 'text-on-surface-variant',
                    ]"
                    @click="closeMobileSidebar"
                >
                    <SettingsIcon :size="16" />
                </Link>
                <button
                    @click="logout"
                    class="rounded-lg p-2 text-on-surface-variant transition-all hover:text-error-red"
                >
                    <LogOut :size="16" />
                </button>
            </div>
        </header>

        <div
            v-if="mobileSidebarOpen"
            class="glass-panel z-20 max-h-64 overflow-y-auto border-b border-outline-glass px-4 py-3 md:hidden"
        >
            <p
                class="mb-2 text-[10px] font-bold tracking-wider text-on-surface-variant uppercase opacity-75"
            >
                {{ t('collections.label') }}
            </p>
            <div v-if="collections.length > 0" class="space-y-1">
                <div
                    v-for="collection in collections"
                    :key="collection.id"
                    class="relative flex items-center justify-between rounded-xl px-3 py-2 text-xs font-semibold"
                    :class="[
                        activeCollectionId === collection.id
                            ? 'bg-surface-container-low font-bold text-primary'
                            : 'text-on-surface-variant',
                    ]"
                >
                    <Link
                        :href="`/collections/${collection.id}`"
                        class="flex flex-1 items-center gap-3 truncate pr-8 text-left"
                        @click="closeMobileSidebar"
                    >
                        <span v-if="collection.icon" class="shrink-0 text-sm">{{
                            collection.icon
                        }}</span>
                        <BookOpen v-else :size="14" class="shrink-0" />
                        <span class="truncate">{{ collection.title }}</span>
                    </Link>
                    <button
                        type="button"
                        @click.stop="deleteCollection(collection.id)"
                        class="absolute right-2 cursor-pointer rounded-lg p-1 text-on-surface-variant hover:bg-error-red/10 hover:text-error-red"
                        :title="t('collections.delete')"
                    >
                        <Trash2 :size="12" />
                    </button>
                </div>
            </div>
            <p v-else class="px-3 py-2 text-xs text-on-surface-variant">
                {{ t('collections.empty') }}
            </p>
        </div>

        <!-- Main Workspace -->
        <main
            id="main-content"
            class="flex h-screen flex-1 flex-col overflow-hidden"
        >
            <div
                class="relative flex flex-1 flex-col overflow-hidden p-4 md:p-8"
            >
                <!-- Ambient Decorator -->
                <div
                    class="pointer-events-none absolute top-1/2 left-1/2 h-[70vw] w-[70vw] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/5 blur-[100px]"
                ></div>

                <div
                    class="z-10 flex flex-1 flex-col overflow-hidden w-full max-w-5xl mx-auto"
                >
                    <FlashAlerts />
                    <ConfirmDialog />

                    <div class="flex flex-1 flex-col overflow-hidden">
                        <slot />
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
