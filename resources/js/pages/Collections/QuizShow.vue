<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowLeft, Trophy } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CollectionLayout from '@/layouts/CollectionLayout.vue';
import Button from '@/components/ui/Button.vue';
import QuizQuestion from '@/components/collection/QuizQuestion.vue';
import type {
    Collection,
    QuizAttempt,
    QuizQuestion as QuizQuestionType,
    QuizSummary,
} from '@/types';

const props = defineProps<{
    collection: Collection;
    quiz: QuizSummary & { questions_count: number };
    questions: QuizQuestionType[];
    attempts: QuizAttempt[];
}>();

const { t } = useI18n();

const mode = ref<'intro' | 'taking' | 'results'>(
    props.quiz.status === 'completed' ? 'results' : 'intro',
);
const currentIndex = ref(0);
const answers = ref<Record<string, string>>({});
const submitting = ref(false);

const currentQuestion = computed<QuizQuestionType | null>(() => {
    return props.questions[currentIndex.value] ?? null;
});

const lastAttempt = computed<QuizAttempt | null>(() => {
    return props.attempts[0] ?? null;
});

function mistakeQuestion(mistake: unknown): string {
    return String((mistake as Record<string, unknown>).question ?? '');
}

function mistakeUserAnswer(mistake: unknown): string {
    return String((mistake as Record<string, unknown>).user_answer ?? '');
}

function mistakeCorrectAnswer(mistake: unknown): string {
    return String((mistake as Record<string, unknown>).correct_answer ?? '');
}

function startQuiz(): void {
    mode.value = 'taking';
    currentIndex.value = 0;
    answers.value = {};
}

function recordAnswer(answer: string): void {
    if (currentQuestion.value) {
        answers.value[String(currentQuestion.value.id)] = answer;
    }
}

function nextQuestion(): void {
    if (currentIndex.value < props.questions.length - 1) {
        currentIndex.value++;
    } else {
        submitQuiz();
    }
}

function submitQuiz(): void {
    submitting.value = true;
    router.post(
        `/collections/${props.collection.id}/quizzes/${props.quiz.id}/attempt`,
        { answers: answers.value },
        {
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
            },
            onSuccess: () => {
                mode.value = 'results';
            },
        },
    );
}
</script>

<template>
    <CollectionLayout :title="quiz.title" :collection="collection">
        <div class="flex flex-1 flex-col gap-4 overflow-y-auto">
            <button
                class="flex w-fit cursor-pointer items-center gap-1 text-xs text-on-surface-variant transition hover:text-primary"
                @click="router.visit(`/collections/${collection.id}/quizzes`)"
            >
                <ArrowLeft :size="14" />
                {{ t('quizzes.back_to_list') }}
            </button>

            <!-- Intro Mode -->
            <div
                v-if="mode === 'intro'"
                class="flex flex-1 flex-col items-center justify-center gap-6"
            >
                <div class="flex flex-col items-center gap-3 text-center">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-primary/20 bg-primary/5"
                    >
                        <Trophy :size="20" class="text-primary" />
                    </div>
                    <h1 class="font-heading text-lg font-bold text-on-surface">
                        {{ quiz.title }}
                    </h1>
                    <p class="text-sm text-on-surface-variant">
                        {{ questions.length }} {{ t('quizzes.questions') }}
                    </p>
                </div>
                <Button @click="startQuiz">
                    {{ t('quizzes.start') }}
                </Button>
            </div>

            <!-- Taking Mode -->
            <div
                v-if="mode === 'taking' && currentQuestion"
                class="flex flex-1 flex-col gap-4"
            >
                <QuizQuestion
                    :question="currentQuestion"
                    :index="currentIndex"
                    :total="questions.length"
                    :submitting="submitting"
                    @answer="recordAnswer"
                    @next="nextQuestion"
                />
            </div>

            <!-- Results Mode -->
            <div v-if="mode === 'results'" class="flex flex-1 flex-col gap-6">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-primary/20 bg-primary/5"
                    >
                        <Trophy :size="20" class="text-primary" />
                    </div>
                    <h1 class="font-heading text-lg font-bold text-on-surface">
                        {{ quiz.title }}
                    </h1>
                    <p class="font-heading text-3xl font-bold text-primary">
                        {{ lastAttempt ? lastAttempt.score : quiz.score }}%
                    </p>
                    <p class="text-xs text-on-surface-variant">
                        {{ t('quizzes.completed_message') }}
                    </p>
                </div>

                <div
                    v-if="
                        lastAttempt &&
                        lastAttempt.mistakes &&
                        lastAttempt.mistakes.length > 0
                    "
                    class="space-y-2"
                >
                    <h3 class="font-heading text-sm font-bold text-on-surface">
                        {{ t('quizzes.mistakes') }}
                    </h3>
                    <div
                        v-for="(mistake, i) in lastAttempt.mistakes"
                        :key="i"
                        class="rounded-xl border border-error-red/20 bg-error-red/5 p-3"
                    >
                        <p class="text-xs font-bold text-on-surface">
                            {{ mistakeQuestion(mistake) }}
                        </p>
                        <p class="mt-1 text-[10px] text-on-surface-variant">
                            {{ t('quizzes.your_answer') }}:
                            <span class="text-error-red">{{
                                mistakeUserAnswer(mistake)
                            }}</span>
                        </p>
                        <p class="text-[10px] text-on-surface-variant">
                            {{ t('quizzes.correct_answer') }}:
                            <span class="font-bold text-success">{{
                                mistakeCorrectAnswer(mistake)
                            }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-center gap-2">
                    <Button @click="startQuiz">
                        {{ t('quizzes.retry') }}
                    </Button>
                </div>
            </div>
        </div>
    </CollectionLayout>
</template>
