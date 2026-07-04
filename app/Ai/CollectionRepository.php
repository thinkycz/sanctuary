<?php

declare(strict_types=1);

namespace App\Ai;

use App\Enums\LessonStatusEnum;
use App\Enums\TermDifficultyEnum;
use App\Models\Collection;
use App\Models\Flashcard;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Term;
use App\Models\TutorMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Support\Typer;

class CollectionRepository
{
    /**
     * Find a collection owned by the given user.
     */
    public function findOwnedCollection(int $id, User $user): Collection|null
    {
        $collection = Collection::query()
            ->where('id', $id)
            ->where('user_id', $user->getKey())
            ->first();

        return $collection instanceof Collection ? $collection : null;
    }

    /**
     * Find a lesson owned by the given user.
     */
    public function findOwnedLesson(int $id, User $user): Lesson|null
    {
        $lesson = Lesson::query()
            ->where('id', $id)
            ->where('user_id', $user->getKey())
            ->first();

        return $lesson instanceof Lesson ? $lesson : null;
    }

    /**
     * Find a quiz owned by the given user.
     */
    public function findOwnedQuiz(int $id, User $user): Quiz|null
    {
        $quiz = Quiz::query()
            ->where('id', $id)
            ->where('user_id', $user->getKey())
            ->first();

        return $quiz instanceof Quiz ? $quiz : null;
    }

    /**
     * Find a term owned by the given user.
     */
    public function findOwnedTerm(int $id, User $user): Term|null
    {
        $item = Term::query()
            ->where('id', $id)
            ->where('user_id', $user->getKey())
            ->first();

        return $item instanceof Term ? $item : null;
    }

    /**
     * Find a flashcard owned by the given user.
     */
    public function findOwnedFlashcard(int $id, User $user): Flashcard|null
    {
        $flashcard = Flashcard::query()
            ->where('id', $id)
            ->where('user_id', $user->getKey())
            ->first();

        return $flashcard instanceof Flashcard ? $flashcard : null;
    }

    /**
     * Recent collections for the sidebar.
     *
     * @return array<int, array{id: int, title: string, icon: string|null, updated_at: string}>
     */
    public function recentForSidebar(User $user, int $limit = 50): array
    {
        $payload = [];

        foreach ($user->collections()->select(['id', 'title', 'icon', 'updated_at'])->limit($limit)->get() as $collection) {
            $payload[] = [
                'id' => $collection->getId(),
                'title' => $collection->getTitle(),
                'icon' => $collection->getIcon(),
                'updated_at' => $this->serializeTimestamp($collection->getUpdatedAt()),
            ];
        }

        return $payload;
    }

    /**
     * Serialize a collection for the overview page.
     *
     * @return array<string, mixed>
     */
    public function collectionOverview(Collection $collection): array
    {
        $lessons = $collection->lessons()->orderByDesc('created_at')->limit(20)->get();
        $readyCount = $collection->lessons()->where('status', LessonStatusEnum::Ready->value)->count();
        $termsCount = $collection->terms()->count();
        $dueFlashcards = $collection->flashcards()
            ->where(function ($query): void {
                $query->whereNull('due_at')->orWhere('due_at', '<=', Carbon::now());
            })
            ->count();

        $avgScore = $this->averageQuizScore($collection);

        return [
            'collection' => $this->serializeCollection($collection),
            'lessons' => $lessons->map(fn(Lesson $lesson): array => $this->serializeLessonSummary($lesson))->all(),
            'stats' => [
                'lessons_ready' => $readyCount,
                'terms_learned' => $termsCount,
                'flashcards_due' => $dueFlashcards,
                'average_quiz_score' => $avgScore,
            ],
        ];
    }

    /**
     * Serialize lessons for the lessons list page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lessonsForList(Collection $collection): array
    {
        $lessons = $collection->lessons()->orderByDesc('created_at')->get();

        return $lessons->map(fn(Lesson $lesson): array => $this->serializeLessonSummary($lesson))->values()->all();
    }

    /**
     * Serialize a full lesson for the detail page.
     *
     * @return array<string, mixed>
     */
    public function lessonDetail(Lesson $lesson): array
    {
        $collection = $lesson->collection()->first();

        return [
            'lesson' => $this->serializeLessonDetail($lesson),
            'collection' => $collection instanceof Collection ? $this->serializeCollection($collection) : null,
            'terms' => $lesson->terms()->get()->map(fn(Term $item): array => $this->serializeTerm($item))->values()->all(),
            'flashcards' => $lesson->flashcards()->get()->map(fn(Flashcard $card): array => $this->serializeFlashcard($card))->values()->all(),
            'quizzes' => $lesson->quizzes()->get()->map(fn(Quiz $quiz): array => $this->serializeQuizSummary($quiz))->values()->all(),
            'tutorMessages' => $lesson->tutorMessages()->orderBy('created_at')->get()->map(fn(TutorMessage $msg): array => $this->serializeTutorMessage($msg))->values()->all(),
        ];
    }

    /**
     * Serialize terms for the terms tab.
     *
     * @return array<int, array<string, mixed>>
     */
    public function termsForCollection(Collection $collection, string|null $search = null, string|null $difficulty = null): array
    {
        $query = $collection->terms()->orderByDesc('created_at');

        if ($search !== null && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('term', 'LIKE', "%{$search}%")
                    ->orWhere('definition', 'LIKE', "%{$search}%");
            });
        }

        if ($difficulty !== null && $difficulty !== '' && \in_array($difficulty, TermDifficultyEnum::values(), true)) {
            $query->where('difficulty', $difficulty);
        }

        return $query->get()->map(fn(Term $item): array => $this->serializeTerm($item))->values()->all();
    }

    /**
     * Serialize flashcards for the flashcards tab.
     *
     * @return array<int, array<string, mixed>>
     */
    public function flashcardsForCollection(Collection $collection): array
    {
        return $collection->flashcards()
            ->orderBy('due_at')
            ->get()
            ->map(fn(Flashcard $card): array => $this->serializeFlashcard($card))
            ->values()
            ->all();
    }

    /**
     * Serialize quizzes for the quizzes tab.
     *
     * @return array<int, array<string, mixed>>
     */
    public function quizzesForCollection(Collection $collection): array
    {
        return $collection->quizzes()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Quiz $quiz): array => $this->serializeQuizSummary($quiz))
            ->values()
            ->all();
    }

    /**
     * Serialize a quiz with its questions for the quiz-taking page.
     *
     * @return array<string, mixed>
     */
    public function quizDetail(Quiz $quiz): array
    {
        return [
            'quiz' => $this->serializeQuizDetail($quiz),
            'questions' => $quiz->questions()->get()->map(fn(QuizQuestion $question): array => $this->serializeQuizQuestion($question))->all(),
            'attempts' => $quiz->attempts()->orderByDesc('created_at')->limit(10)->get()->map(fn(QuizAttempt $attempt): array => $this->serializeQuizAttempt($attempt))->all(),
        ];
    }

    /**
     * Serialize tutor messages for the tutor tab.
     *
     * @return array<int, array<string, mixed>>
     */
    public function tutorMessagesForCollection(Collection $collection): array
    {
        return $collection->tutorMessages()
            ->orderBy('created_at')
            ->get()
            ->map(fn(TutorMessage $msg): array => $this->serializeTutorMessage($msg))
            ->values()
            ->all();
    }

    /**
     * Serialize progress analytics for the progress tab.
     *
     * @return array<string, mixed>
     */
    public function progressForCollection(Collection $collection): array
    {
        $lessonsReady = $collection->lessons()->where('status', LessonStatusEnum::Ready->value)->count();
        $lessonsMastered = $collection->lessons()->where('progress_status', 'mastered')->count();
        $termsLearned = $collection->terms()->whereIn('difficulty', [TermDifficultyEnum::Learning->value, TermDifficultyEnum::Mastered->value])->count();
        $termsMastered = $collection->terms()->where('difficulty', TermDifficultyEnum::Mastered->value)->count();
        $flashcardsReviewed = $collection->flashcards()->where('review_count', '>', 0)->count();
        $avgScore = $this->averageQuizScore($collection);

        $recentAttempts = $collection->quizzes()
            ->with('attempts')
            ->get()
            ->flatMap(fn(Quiz $quiz): EloquentCollection => $quiz->attempts()->get())
            ->sortByDesc('created_at')
            ->take(10)
            ->map(fn(QuizAttempt $attempt): array => $this->serializeQuizAttempt($attempt))
            ->values()
            ->all();

        return [
            'collection' => $this->serializeCollection($collection),
            'stats' => [
                'lessons_ready' => $lessonsReady,
                'lessons_mastered' => $lessonsMastered,
                'terms_learned' => $termsLearned,
                'terms_mastered' => $termsMastered,
                'flashcards_reviewed' => $flashcardsReviewed,
                'average_quiz_score' => $avgScore,
            ],
            'recent_attempts' => $recentAttempts,
        ];
    }

    /**
     * Serialize a collection to a plain array.
     *
     * @return array<string, mixed>
     */
    public function serializeCollection(Collection $collection): array
    {
        return [
            'id' => $collection->getId(),
            'title' => $collection->getTitle(),
            'description' => $collection->getDescription(),
            'icon' => $collection->getIcon(),
            'subject' => $collection->getSubject(),
            'created_at' => $this->serializeTimestamp($collection->getCreatedAt()),
            'updated_at' => $this->serializeTimestamp($collection->getUpdatedAt()),
        ];
    }

    /**
     * Serialize a lesson summary (for lists).
     *
     * @return array<string, mixed>
     */
    public function serializeLessonSummary(Lesson $lesson): array
    {
        return [
            'id' => $lesson->getId(),
            'collection_id' => $lesson->getCollectionId(),
            'title' => $lesson->getTitle(),
            'source_type' => $lesson->getSourceType()->value,
            'difficulty' => $lesson->getDifficulty()->value,
            'status' => $lesson->getStatus()->value,
            'progress_status' => $lesson->getProgressStatus()->value,
            'error_message' => $lesson->getErrorMessage(),
            'completed_at' => $lesson->getCompletedAt()?->toJSON(),
            'created_at' => $this->serializeTimestamp($lesson->getCreatedAt()),
            'updated_at' => $this->serializeTimestamp($lesson->getUpdatedAt()),
        ];
    }

    /**
     * Serialize a full lesson (for detail page).
     *
     * @return array<string, mixed>
     */
    public function serializeLessonDetail(Lesson $lesson): array
    {
        return [
            ...$this->serializeLessonSummary($lesson),
            'source_text' => $lesson->getSourceText(),
            'quick_summary' => $lesson->getQuickSummary(),
            'simple_explanation' => $lesson->getSimpleExplanation(),
            'deep_explanation' => $lesson->getDeepExplanation(),
            'ai_raw_response' => $lesson->getAiRawResponse(),
        ];
    }

    /**
     * Serialize a term.
     *
     * @return array<string, mixed>
     */
    public function serializeTerm(Term $item): array
    {
        return [
            'id' => $item->getId(),
            'collection_id' => $item->getCollectionId(),
            'lesson_id' => $item->getLessonId(),
            'term' => $item->getTerm(),
            'definition' => $item->getDefinition(),
            'category' => $item->getCategory(),
            'example' => $item->getExample(),
            'difficulty' => $item->getDifficulty()->value,
            'last_reviewed_at' => $item->getLastReviewedAt()?->toJSON(),
            'created_at' => $this->serializeTimestamp($item->getCreatedAt()),
        ];
    }

    /**
     * Serialize a flashcard.
     *
     * @return array<string, mixed>
     */
    public function serializeFlashcard(Flashcard $card): array
    {
        return [
            'id' => $card->getId(),
            'collection_id' => $card->getCollectionId(),
            'lesson_id' => $card->getLessonId(),
            'term_id' => $card->getTermId(),
            'front' => $card->getFront(),
            'back' => $card->getBack(),
            'example' => $card->getExample(),
            'difficulty' => $card->getDifficulty()->value,
            'review_count' => $card->getReviewCount(),
            'due_at' => $card->getDueAt()?->toJSON(),
            'last_reviewed_at' => $card->getLastReviewedAt()?->toJSON(),
            'created_at' => $this->serializeTimestamp($card->getCreatedAt()),
        ];
    }

    /**
     * Serialize a quiz summary (for lists).
     *
     * @return array<string, mixed>
     */
    public function serializeQuizSummary(Quiz $quiz): array
    {
        $lesson = $quiz->lesson()->first();

        return [
            'id' => $quiz->getId(),
            'collection_id' => $quiz->getCollectionId(),
            'lesson_id' => $quiz->getLessonId(),
            'lesson_title' => $lesson instanceof Lesson ? $lesson->getTitle() : null,
            'title' => $quiz->getTitle(),
            'status' => $quiz->getStatus()->value,
            'score' => $quiz->getScore(),
            'total_questions' => $quiz->getTotalQuestions(),
            'completed_at' => $quiz->getCompletedAt()?->toJSON(),
            'created_at' => $this->serializeTimestamp($quiz->getCreatedAt()),
        ];
    }

    /**
     * Serialize a quiz with question count (for detail page).
     *
     * @return array<string, mixed>
     */
    public function serializeQuizDetail(Quiz $quiz): array
    {
        return [
            ...$this->serializeQuizSummary($quiz),
            'questions_count' => $quiz->questions()->count(),
        ];
    }

    /**
     * Serialize a quiz question.
     *
     * @return array<string, mixed>
     */
    public function serializeQuizQuestion(QuizQuestion $question): array
    {
        return [
            'id' => $question->getId(),
            'quiz_id' => $question->getQuizId(),
            'type' => $question->getType()->value,
            'question' => $question->getQuestion(),
            'options' => $question->getOptions(),
            'correct_answer' => $question->getCorrectAnswer(),
            'explanation' => $question->getExplanation(),
            'order' => $question->getOrder(),
        ];
    }

    /**
     * Serialize a quiz attempt.
     *
     * @return array<string, mixed>
     */
    public function serializeQuizAttempt(QuizAttempt $attempt): array
    {
        return [
            'id' => $attempt->getId(),
            'quiz_id' => $attempt->getQuizId(),
            'score' => $attempt->getScore(),
            'answers' => $attempt->getAnswers(),
            'mistakes' => $attempt->getMistakes(),
            'completed_at' => $attempt->getCompletedAt()?->toJSON(),
            'created_at' => $this->serializeTimestamp($attempt->getCreatedAt()),
        ];
    }

    /**
     * Serialize a tutor message.
     *
     * @return array<string, mixed>
     */
    public function serializeTutorMessage(TutorMessage $msg): array
    {
        return [
            'id' => $msg->getId(),
            'collection_id' => $msg->getCollectionId(),
            'lesson_id' => $msg->getLessonId(),
            'role' => $msg->getRole()->value,
            'content' => $msg->getContent(),
            'created_at' => $this->serializeTimestamp($msg->getCreatedAt()),
        ];
    }

    /**
     * Calculate the average quiz score for a collection.
     */
    private function averageQuizScore(Collection $collection): int|null
    {
        $avg = $collection->quizzes()
            ->whereNotNull('score')
            ->avg('score');

        return \is_numeric($avg) ? (int) \round((float) $avg) : null;
    }

    /**
     * Serialize a timestamp to its JSON representation.
     */
    private function serializeTimestamp(Carbon $carbon): string
    {
        return Typer::assertString($carbon->toJSON());
    }
}
