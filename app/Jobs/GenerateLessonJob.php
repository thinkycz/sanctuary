<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\LessonGenerationFailedException;
use App\Ai\LessonGenerationService;
use App\Enums\FlashcardDifficultyEnum;
use App\Enums\LessonStatusEnum;
use App\Enums\QuizQuestionTypeEnum;
use App\Enums\QuizStatusEnum;
use App\Enums\TermDifficultyEnum;
use App\Models\Collection;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Thinkycz\LaravelCore\Support\Resolver;
use Throwable;

class GenerateLessonJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $lessonId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LessonGenerationService $service): void
    {
        $lesson = Lesson::query()->where('id', $this->lessonId)->first();

        if (!$lesson instanceof Lesson) {
            return;
        }

        if ($lesson->getStatus() === LessonStatusEnum::Failed) {
            return;
        }

        $user = $this->user($lesson);
        $this->authenticateAs($user);

        $lesson->forceFill([
            'status' => LessonStatusEnum::Generating->value,
        ])->save();

        try {
            $sourceText = $lesson->getSourceText() ?? '';
            $language = $user->getLocale();
            $difficulty = $lesson->getDifficulty();
            $subject = $this->resolveSubject($lesson);

            $result = $service->generate($sourceText, $language, $difficulty, [], $subject);

            $this->persistResult($lesson, $result);
        } catch (LessonGenerationFailedException $e) {
            $this->markFailed($lesson, $e->getMessage());
        } catch (Throwable $e) {
            $this->markFailed($lesson, $e->getMessage());
        }
    }

    /**
     * Resolve the subject from the lesson's collection.
     */
    private function resolveSubject(Lesson $lesson): string
    {
        $collection = $lesson->collection()->first();

        if ($collection instanceof Collection) {
            return $collection->getSubject() ?? '';
        }

        return '';
    }

    /**
     * Persist the generated lesson result and related entities.
     */
    private function persistResult(Lesson $lesson, \App\Ai\LessonGenerationResult $result): void
    {
        DB::transaction(function () use ($lesson, $result): void {
            $lesson->forceFill([
                'title' => $result->title,
                'status' => LessonStatusEnum::Ready->value,
                'quick_summary' => $result->quickSummary,
                'simple_explanation' => $result->simpleExplanation,
                'deep_explanation' => $result->deepExplanation,
                'ai_raw_response' => $result->raw,
                'error_message' => null,
                'completed_at' => \now(),
            ])->save();

            foreach ($result->terms as $term) {
                $lesson->terms()->create([
                    'user_id' => $lesson->getUserId(),
                    'collection_id' => $lesson->getCollectionId(),
                    'term' => $term['term'],
                    'definition' => $term['definition'],
                    'category' => $term['category'] !== '' ? $term['category'] : null,
                    'example' => $term['example'] !== '' ? $term['example'] : null,
                    'difficulty' => TermDifficultyEnum::Unknown->value,
                ]);
            }

            foreach ($result->flashcards as $card) {
                $lesson->flashcards()->create([
                    'user_id' => $lesson->getUserId(),
                    'collection_id' => $lesson->getCollectionId(),
                    'front' => $card['front'],
                    'back' => $card['back'],
                    'example' => $card['example'] !== '' ? $card['example'] : null,
                    'difficulty' => FlashcardDifficultyEnum::Again->value,
                    'review_count' => 0,
                    'due_at' => \now(),
                ]);
            }

            $quizQuestions = $result->quiz['questions'];
            if ($quizQuestions !== []) {
                $quiz = $lesson->quizzes()->create([
                    'user_id' => $lesson->getUserId(),
                    'collection_id' => $lesson->getCollectionId(),
                    'title' => $result->quiz['title'],
                    'status' => QuizStatusEnum::NotStarted->value,
                    'total_questions' => \count($quizQuestions),
                ]);

                foreach ($quizQuestions as $index => $question) {
                    $type = \in_array($question['type'], QuizQuestionTypeEnum::values(), true)
                        ? $question['type']
                        : QuizQuestionTypeEnum::MultipleChoice->value;

                    $quiz->questions()->create([
                        'type' => $type,
                        'question' => $question['question'],
                        'options' => $question['options'] !== [] ? $question['options'] : null,
                        'correct_answer' => $question['correct_answer'],
                        'explanation' => $question['explanation'] !== '' ? $question['explanation'] : null,
                        'order' => $index,
                    ]);
                }
            }
        });
    }

    /**
     * Mark the lesson as failed.
     */
    private function markFailed(Lesson $lesson, string $message): void
    {
        $lesson->forceFill([
            'status' => LessonStatusEnum::Failed->value,
            'error_message' => $message,
        ])->save();
    }

    /**
     * Load the lesson owner.
     */
    private function user(Lesson $lesson): User
    {
        $user = User::query()->where('id', $lesson->getUserId())->first();

        if (!$user instanceof User) {
            throw new RuntimeException('Lesson owner does not exist.');
        }

        return $user;
    }

    /**
     * Set the queue process auth context.
     */
    private function authenticateAs(User $user): void
    {
        Resolver::resolveAuthManager()->shouldUse('users');
        Resolver::resolveAuthManager()->guard('users')->setUser($user);
    }
}
