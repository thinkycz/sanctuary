<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from '@/components/ui/Button.vue';
import type { QuizQuestion } from '@/types';

const props = defineProps<{
    question: QuizQuestion;
    index: number;
    total: number;
    submitting?: boolean;
}>();

const emit = defineEmits<{
    answer: [answer: string];
    next: [];
}>();

const { t } = useI18n();
const selectedAnswer = ref('');
const textAnswer = ref('');
const showResult = ref(false);

const isCorrect = computed(() => {
    const answer =
        props.question.type === 'multiple_choice'
            ? selectedAnswer.value
            : textAnswer.value;
    return (
        answer.trim().toLowerCase() ===
        props.question.correct_answer.trim().toLowerCase()
    );
});

const userAnswer = computed(() => {
    return props.question.type === 'multiple_choice'
        ? selectedAnswer.value
        : textAnswer.value;
});

function submit(): void {
    showResult.value = true;
    emit('answer', userAnswer.value);
}

function next(): void {
    showResult.value = false;
    selectedAnswer.value = '';
    textAnswer.value = '';
    emit('next');
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <span
                class="text-[10px] font-bold tracking-wider text-on-surface-variant uppercase"
            >
                {{ t('quizzes.question') }} {{ index + 1 }} / {{ total }}
            </span>
        </div>

        <h3 class="font-heading text-base font-bold text-on-surface">
            {{ question.question }}
        </h3>

        <!-- Multiple Choice -->
        <div
            v-if="question.type === 'multiple_choice' && question.options"
            class="space-y-2"
        >
            <button
                v-for="option in question.options"
                :key="option"
                type="button"
                :disabled="showResult"
                :class="[
                    'flex w-full cursor-pointer items-center gap-3 rounded-xl border p-3 text-left text-sm font-semibold transition',
                    showResult && option === question.correct_answer
                        ? 'border-success bg-success/10 text-success'
                        : showResult &&
                            option === selectedAnswer &&
                            option !== question.correct_answer
                          ? 'border-error-red bg-error-red/10 text-error-red'
                          : selectedAnswer === option
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-outline-glass text-on-surface hover:bg-surface-container-low',
                    showResult ? 'cursor-default' : '',
                ]"
                @click="selectedAnswer = option"
            >
                <span class="text-xs">{{ option }}</span>
            </button>
        </div>

        <!-- Text Input (fill_blank) -->
        <div v-else>
            <input
                v-model="textAnswer"
                type="text"
                :disabled="showResult"
                :placeholder="t('quizzes.type_answer')"
                class="w-full rounded-xl border border-outline-glass bg-surface-container-low px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none"
                @keydown.enter="submit"
            />
        </div>

        <!-- Result -->
        <div
            v-if="showResult"
            class="space-y-3 rounded-xl border border-outline-glass bg-surface-container-low p-4"
        >
            <div class="flex items-center gap-2">
                <span
                    :class="[
                        'rounded-lg px-2 py-1 text-[10px] font-bold',
                        isCorrect
                            ? 'bg-success/10 text-success'
                            : 'bg-error-red/10 text-error-red',
                    ]"
                >
                    {{
                        isCorrect
                            ? t('quizzes.correct')
                            : t('quizzes.incorrect')
                    }}
                </span>
            </div>
            <div v-if="!isCorrect">
                <p class="text-xs text-on-surface-variant">
                    {{ t('quizzes.correct_answer') }}:
                    <span class="font-bold text-on-surface">{{
                        question.correct_answer
                    }}</span>
                </p>
            </div>
            <p
                v-if="question.explanation"
                class="text-xs text-on-surface-variant"
            >
                {{ question.explanation }}
            </p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end">
            <Button
                v-if="!showResult"
                :disabled="!userAnswer.trim()"
                @click="submit"
            >
                {{ t('quizzes.submit') }}
            </Button>
            <Button v-else :disabled="props.submitting" @click="next">
                {{
                    props.submitting
                        ? t('quizzes.submit')
                        : index + 1 < total
                          ? t('quizzes.next')
                          : t('quizzes.finish')
                }}
            </Button>
        </div>
    </div>
</template>
