<?php

declare(strict_types=1);

namespace App\Ai;

/**
 * Validated lesson generation result.
 *
 * Immutable DTO produced by {@see LessonGenerationService} after the AI
 * returns and validates the structured lesson JSON.
 */
final class LessonGenerationResult
{
    /**
     * @param array<int, string> $quickSummary
     * @param array<string, mixed> $deepExplanation
     * @param array<int, array{term: string, definition: string, category: string, example: string}> $terms
     * @param array<int, array{front: string, back: string, example: string}> $flashcards
     * @param array{title: string, questions: array<int, array{type: string, question: string, options: array<int, string>, correct_answer: string, explanation: string}>} $quiz
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $title,
        public readonly array $quickSummary,
        public readonly string $simpleExplanation,
        public readonly array $deepExplanation,
        public readonly array $terms,
        public readonly array $flashcards,
        public readonly array $quiz,
        public readonly array $raw,
    ) {}

    /**
     * Build a result from the validated AI response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $quickSummary = [];
        $rawQuickSummary = $data['quick_summary'] ?? [];
        if (\is_array($rawQuickSummary)) {
            foreach ($rawQuickSummary as $item) {
                if (\is_string($item)) {
                    $quickSummary[] = $item;
                }
            }
        }

        $deepExplanation = [];
        $rawDeepExplanation = $data['deep_explanation'] ?? null;
        if (\is_array($rawDeepExplanation)) {
            foreach ($rawDeepExplanation as $key => $value) {
                if (\is_string($key)) {
                    $deepExplanation[$key] = $value;
                }
            }
        }

        $terms = [];
        $rawTerms = $data['terms'] ?? [];
        if (\is_array($rawTerms)) {
            foreach ($rawTerms as $item) {
                if (\is_array($item)) {
                    $terms[] = self::normalizeTerm($item);
                }
            }
        }

        $flashcards = [];
        $rawFlashcards = $data['flashcards'] ?? [];
        if (\is_array($rawFlashcards)) {
            foreach ($rawFlashcards as $item) {
                if (\is_array($item)) {
                    $flashcards[] = self::normalizeFlashcard($item);
                }
            }
        }

        $rawQuiz = $data['quiz'] ?? null;
        $quizData = \is_array($rawQuiz) ? $rawQuiz : [];
        $quizTitle = \is_string($quizData['title'] ?? null) ? $quizData['title'] : 'Quiz';
        $questions = [];
        $rawQuestions = $quizData['questions'] ?? [];
        if (\is_array($rawQuestions)) {
            foreach ($rawQuestions as $item) {
                if (\is_array($item)) {
                    $questions[] = self::normalizeQuestion($item);
                }
            }
        }

        return new self(
            title: \is_string($data['title'] ?? null) ? $data['title'] : 'Untitled Lesson',
            quickSummary: $quickSummary,
            simpleExplanation: \is_string($data['simple_explanation'] ?? null) ? $data['simple_explanation'] : '',
            deepExplanation: $deepExplanation,
            terms: $terms,
            flashcards: $flashcards,
            quiz: ['title' => $quizTitle, 'questions' => $questions],
            raw: $data,
        );
    }

    /**
     * @param array<mixed, mixed> $item
     *
     * @return array{term: string, definition: string, category: string, example: string}
     */
    private static function normalizeTerm(array $item): array
    {
        return [
            'term' => \is_string($item['term'] ?? null) ? $item['term'] : '',
            'definition' => \is_string($item['definition'] ?? null) ? $item['definition'] : '',
            'category' => \is_string($item['category'] ?? null) ? $item['category'] : '',
            'example' => \is_string($item['example'] ?? null) ? $item['example'] : '',
        ];
    }

    /**
     * @param array<mixed, mixed> $item
     *
     * @return array{front: string, back: string, example: string}
     */
    private static function normalizeFlashcard(array $item): array
    {
        return [
            'front' => \is_string($item['front'] ?? null) ? $item['front'] : '',
            'back' => \is_string($item['back'] ?? null) ? $item['back'] : '',
            'example' => \is_string($item['example'] ?? null) ? $item['example'] : '',
        ];
    }

    /**
     * @param array<mixed, mixed> $item
     *
     * @return array{type: string, question: string, options: array<int, string>, correct_answer: string, explanation: string}
     */
    private static function normalizeQuestion(array $item): array
    {
        $options = [];
        $rawOptions = $item['options'] ?? [];
        if (\is_array($rawOptions)) {
            foreach ($rawOptions as $option) {
                if (\is_string($option)) {
                    $options[] = $option;
                }
            }
        }

        return [
            'type' => \is_string($item['type'] ?? null) ? $item['type'] : 'multiple_choice',
            'question' => \is_string($item['question'] ?? null) ? $item['question'] : '',
            'options' => $options,
            'correct_answer' => \is_string($item['correct_answer'] ?? null) ? $item['correct_answer'] : '',
            'explanation' => \is_string($item['explanation'] ?? null) ? $item['explanation'] : '',
        ];
    }
}
