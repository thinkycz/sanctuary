<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    RefreshCw,
    Trash2,
    ChevronDown,
    ChevronUp,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import AITutorPanel from '@/components/collection/AITutorPanel.vue';
import SkeletonLesson from '@/components/collection/SkeletonLesson.vue';
import Button from '@/components/ui/Button.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import type {
    Collection,
    Flashcard,
    LessonDetail,
    QuizSummary,
    Term,
    TutorMessage,
} from '@/types';

const props = defineProps<{
    lesson: LessonDetail;
    collection: Collection | null;
    terms: Term[];
    flashcards: Flashcard[];
    quizzes: QuizSummary[];
    tutorMessages: TutorMessage[];
}>();

const { t } = useI18n();
const showOriginal = ref(false);
const activeTab = ref<'content' | 'terms' | 'flashcards' | 'quiz' | 'tutor'>(
    'content',
);

const isGenerating = computed(
    () =>
        props.lesson.status === 'pending' ||
        props.lesson.status === 'generating',
);
const isFailed = computed(() => props.lesson.status === 'failed');

let pollInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    if (isGenerating.value) {
        pollInterval = setInterval(() => {
            router.reload({
                only: [
                    'lesson',
                    'terms',
                    'flashcards',
                    'quizzes',
                    'tutorMessages',
                ],
            });
        }, 3000);
    }
});

onUnmounted(() => {
    if (pollInterval !== null) {
        clearInterval(pollInterval);
    }
});

async function deleteLesson(): Promise<void> {
    const confirmDialog = useConfirmDialog();
    if (await confirmDialog.confirm(t('lessons.delete_confirm'))) {
        router.delete(`/lessons/${props.lesson.id}`);
    }
}

function regenerate(): void {
    router.post(`/lessons/${props.lesson.id}/regenerate`);
}

const difficultyLabel = computed(() => {
    const map: Record<string, string> = {
        beginner: t('lessons.difficulty.beginner'),
        intermediate: t('lessons.difficulty.intermediate'),
        advanced: t('lessons.difficulty.advanced'),
    };
    return map[props.lesson.difficulty] ?? props.lesson.difficulty;
});

const tabs = computed(() => [
    { key: 'content' as const, label: t('lessons.tabs.content'), count: null },
    {
        key: 'terms' as const,
        label: t('lessons.tabs.terms'),
        count: props.terms.length,
    },
    {
        key: 'flashcards' as const,
        label: t('lessons.tabs.flashcards'),
        count: props.flashcards.length,
    },
    {
        key: 'quiz' as const,
        label: t('lessons.tabs.quiz'),
        count: props.quizzes.length,
    },
    {
        key: 'tutor' as const,
        label: t('lessons.tabs.tutor'),
        count: props.tutorMessages.length,
    },
]);
</script>

<template>
    <AppLayout :title="lesson.title">
        <div class="flex flex-1 flex-col gap-4 overflow-hidden">
            <!-- Back Link + Header -->
            <div class="flex items-center gap-3">
                <button
                    v-if="collection"
                    class="flex cursor-pointer items-center gap-1 text-xs text-on-surface-variant transition hover:text-primary"
                    @click="
                        router.visit(`/collections/${collection?.id}/lessons`)
                    "
                >
                    <ArrowLeft :size="14" />
                    {{ collection.title }}
                </button>
            </div>

            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h1 class="font-heading text-xl font-bold text-on-surface">
                        {{ lesson.title }}
                    </h1>
                    <div
                        class="mt-1 flex items-center gap-2 text-[10px] text-on-surface-variant"
                    >
                        <span
                            class="rounded-lg bg-surface-container px-2 py-0.5 font-semibold"
                            >{{ difficultyLabel }}</span
                        >
                        <span
                            v-if="collection?.subject"
                            class="font-semibold"
                            >{{ collection.subject }}</span
                        >
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    <button
                        v-if="isFailed"
                        class="flex cursor-pointer items-center gap-1 rounded-lg border border-outline-glass p-2 text-on-surface-variant transition hover:text-primary"
                        :title="t('lessons.regenerate')"
                        @click="regenerate"
                    >
                        <RefreshCw :size="14" />
                    </button>
                    <button
                        class="flex cursor-pointer items-center gap-1 rounded-lg border border-outline-glass p-2 text-on-surface-variant transition hover:text-error-red"
                        :title="t('lessons.delete')"
                        @click="deleteLesson"
                    >
                        <Trash2 :size="14" />
                    </button>
                </div>
            </div>

            <!-- Generating State -->
            <div
                v-if="isGenerating"
                class="flex flex-1 items-center justify-center"
            >
                <SkeletonLesson />
            </div>

            <!-- Failed State -->
            <div
                v-else-if="isFailed"
                class="flex flex-1 flex-col items-center justify-center gap-4"
            >
                <div class="flex flex-col items-center gap-2 text-center">
                    <p class="text-sm font-semibold text-error-red">
                        {{ t('lessons.failed_title') }}
                    </p>
                    <p class="max-w-md text-xs text-on-surface-variant">
                        {{ lesson.error_message }}
                    </p>
                    <Button @click="regenerate">
                        <RefreshCw :size="14" />
                        {{ t('lessons.regenerate') }}
                    </Button>
                </div>
            </div>

            <!-- Ready State -->
            <div v-else class="flex flex-1 flex-col overflow-hidden">
                <!-- Tabs -->
                <nav class="mb-4 flex gap-1 border-b border-outline-glass">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        :class="[
                            'flex shrink-0 cursor-pointer items-center gap-1.5 border-b-2 px-3 py-2 text-xs font-semibold transition',
                            activeTab === tab.key
                                ? 'border-primary text-primary'
                                : 'border-transparent text-on-surface-variant hover:text-on-surface',
                        ]"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                        <span
                            v-if="tab.count !== null"
                            class="text-[9px] opacity-70"
                            >({{ tab.count }})</span
                        >
                    </button>
                </nav>

                <div class="flex-1 overflow-y-auto">
                    <!-- Content Tab -->
                    <div v-if="activeTab === 'content'" class="space-y-4">
                        <!-- Quick Summary -->
                        <div
                            v-if="
                                lesson.quick_summary &&
                                lesson.quick_summary.length > 0
                            "
                            class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                        >
                            <h3
                                class="mb-2 font-heading text-sm font-bold text-on-surface"
                            >
                                {{ t('lessons.quick_summary') }}
                            </h3>
                            <ul class="space-y-1.5">
                                <li
                                    v-for="(point, i) in lesson.quick_summary"
                                    :key="i"
                                    class="flex items-start gap-2 text-xs text-on-surface-variant"
                                >
                                    <span
                                        class="mt-1 h-1 w-1 shrink-0 rounded-full bg-primary"
                                    ></span>
                                    {{ point }}
                                </li>
                            </ul>
                        </div>

                        <!-- Simple Explanation -->
                        <div
                            v-if="lesson.simple_explanation"
                            class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                        >
                            <h3
                                class="mb-2 font-heading text-sm font-bold text-on-surface"
                            >
                                {{ t('lessons.simple_explanation') }}
                            </h3>
                            <p
                                class="whitespace-pre-line text-sm text-on-surface-variant"
                            >
                                {{ lesson.simple_explanation }}
                            </p>
                        </div>

                        <!-- Deep Explanation -->
                        <div v-if="lesson.deep_explanation" class="space-y-3">
                            <div
                                v-if="
                                    lesson.deep_explanation.key_concepts &&
                                    lesson.deep_explanation.key_concepts
                                        .length > 0
                                "
                                class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                            >
                                <h3
                                    class="mb-3 font-heading text-sm font-bold text-on-surface"
                                >
                                    {{ t('lessons.key_concepts') }}
                                </h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="(note, i) in lesson
                                            .deep_explanation.key_concepts"
                                        :key="i"
                                        class="space-y-1"
                                    >
                                        <p
                                            class="text-xs font-bold text-primary"
                                        >
                                            {{ note.title }}
                                        </p>
                                        <p
                                            class="text-xs text-on-surface-variant"
                                        >
                                            {{ note.explanation }}
                                        </p>
                                        <div
                                            v-if="note.examples.length > 0"
                                            class="space-y-0.5 pl-3"
                                        >
                                            <p
                                                v-for="(ex, j) in note.examples"
                                                :key="j"
                                                class="text-[11px] italic text-on-surface-variant"
                                            >
                                                • {{ ex }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="
                                    lesson.deep_explanation.worked_examples &&
                                    lesson.deep_explanation.worked_examples
                                        .length > 0
                                "
                                class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                            >
                                <h3
                                    class="mb-3 font-heading text-sm font-bold text-on-surface"
                                >
                                    {{ t('lessons.worked_examples') }}
                                </h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="(ex, i) in lesson
                                            .deep_explanation.worked_examples"
                                        :key="i"
                                        class="space-y-1"
                                    >
                                        <p
                                            class="text-xs font-bold text-on-surface"
                                        >
                                            {{ ex.problem }}
                                        </p>
                                        <p
                                            class="text-[11px] text-on-surface-variant"
                                        >
                                            {{ ex.solution }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="
                                    lesson.deep_explanation.notes &&
                                    lesson.deep_explanation.notes.length > 0
                                "
                                class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                            >
                                <h3
                                    class="mb-2 font-heading text-sm font-bold text-on-surface"
                                >
                                    {{ t('lessons.notes') }}
                                </h3>
                                <ul class="space-y-1.5">
                                    <li
                                        v-for="(note, i) in lesson
                                            .deep_explanation.notes"
                                        :key="i"
                                        class="flex items-start gap-2 text-xs text-on-surface-variant"
                                    >
                                        <span
                                            class="mt-1 h-1 w-1 shrink-0 rounded-full bg-primary"
                                        ></span>
                                        {{ note }}
                                    </li>
                                </ul>
                            </div>

                            <div
                                v-if="
                                    lesson.deep_explanation.common_mistakes &&
                                    lesson.deep_explanation.common_mistakes
                                        .length > 0
                                "
                                class="rounded-2xl border border-error-red/20 bg-error-red/5 p-4"
                            >
                                <h3
                                    class="mb-2 font-heading text-sm font-bold text-error-red"
                                >
                                    {{ t('lessons.common_mistakes') }}
                                </h3>
                                <ul class="space-y-1.5">
                                    <li
                                        v-for="(mistake, i) in lesson
                                            .deep_explanation.common_mistakes"
                                        :key="i"
                                        class="flex items-start gap-2 text-xs text-on-surface-variant"
                                    >
                                        <span
                                            class="mt-1 h-1 w-1 shrink-0 rounded-full bg-error-red"
                                        ></span>
                                        {{ mistake }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Original Content -->
                        <div
                            class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-4"
                        >
                            <button
                                class="flex w-full cursor-pointer items-center justify-between text-left"
                                @click="showOriginal = !showOriginal"
                            >
                                <h3
                                    class="font-heading text-sm font-bold text-on-surface"
                                >
                                    {{ t('lessons.original_content') }}
                                </h3>
                                <component
                                    :is="showOriginal ? ChevronUp : ChevronDown"
                                    :size="14"
                                    class="text-on-surface-variant"
                                />
                            </button>
                            <div v-if="showOriginal" class="mt-3">
                                <p
                                    class="whitespace-pre-line text-xs text-on-surface-variant"
                                >
                                    {{ lesson.source_text }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Terms Tab -->
                    <div v-if="activeTab === 'terms'" class="space-y-2">
                        <div
                            v-if="terms.length > 0"
                            class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                        >
                            <div
                                v-for="item in terms"
                                :key="item.id"
                                class="rounded-xl border border-outline-glass bg-surface-container-lowest p-3"
                            >
                                <div class="flex items-baseline gap-2">
                                    <p
                                        class="font-heading text-sm font-bold text-on-surface"
                                    >
                                        {{ item.term }}
                                    </p>
                                </div>
                                <p class="text-xs text-on-surface-variant">
                                    {{ item.definition }}
                                </p>
                                <p
                                    v-if="item.example"
                                    class="mt-1 text-[10px] italic text-on-surface-variant"
                                >
                                    "{{ item.example }}"
                                </p>
                            </div>
                        </div>
                        <p
                            v-else
                            class="py-8 text-center text-xs text-on-surface-variant"
                        >
                            {{ t('terms.empty') }}
                        </p>
                    </div>

                    <!-- Flashcards Tab -->
                    <div v-if="activeTab === 'flashcards'" class="space-y-2">
                        <div
                            v-if="flashcards.length > 0"
                            class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                        >
                            <div
                                v-for="card in flashcards"
                                :key="card.id"
                                class="rounded-xl border border-outline-glass bg-surface-container-lowest p-3"
                            >
                                <p
                                    class="font-heading text-sm font-bold text-on-surface"
                                >
                                    {{ card.front }}
                                </p>
                                <p class="text-xs text-primary">
                                    {{ card.back }}
                                </p>
                                <p
                                    v-if="card.example"
                                    class="mt-1 text-[10px] italic text-on-surface-variant"
                                >
                                    "{{ card.example }}"
                                </p>
                            </div>
                        </div>
                        <p
                            v-else
                            class="py-8 text-center text-xs text-on-surface-variant"
                        >
                            {{ t('flashcards.empty') }}
                        </p>
                    </div>

                    <!-- Quiz Tab -->
                    <div v-if="activeTab === 'quiz'" class="space-y-2">
                        <div v-if="quizzes.length > 0" class="space-y-2">
                            <div
                                v-for="quiz in quizzes"
                                :key="quiz.id"
                                class="flex cursor-pointer items-center justify-between rounded-xl border border-outline-glass bg-surface-container-lowest p-3 transition hover:border-primary/30"
                                @click="
                                    collection &&
                                    router.visit(
                                        `/collections/${collection?.id}/quizzes/${quiz.id}`,
                                    )
                                "
                            >
                                <div>
                                    <p
                                        class="font-heading text-sm font-bold text-on-surface"
                                    >
                                        {{ quiz.title }}
                                    </p>
                                    <p
                                        class="text-[10px] text-on-surface-variant"
                                    >
                                        {{ quiz.total_questions }}
                                        {{ t('quizzes.questions') }}
                                    </p>
                                </div>
                                <span
                                    v-if="quiz.score !== null"
                                    class="rounded-lg bg-primary/10 px-2 py-1 text-[10px] font-bold text-primary"
                                >
                                    {{ quiz.score }}%
                                </span>
                            </div>
                        </div>
                        <p
                            v-else
                            class="py-8 text-center text-xs text-on-surface-variant"
                        >
                            {{ t('quizzes.empty') }}
                        </p>
                    </div>

                    <!-- Tutor Tab -->
                    <div v-if="activeTab === 'tutor'" class="h-full">
                        <AITutorPanel
                            :messages="tutorMessages"
                            :action="`/lessons/${lesson.id}/tutor`"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
