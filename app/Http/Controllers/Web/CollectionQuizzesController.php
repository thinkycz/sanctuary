<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Enums\QuizStatusEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;

class CollectionQuizzesController
{
    use ValidatesWebRequests;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly CollectionRepository $collections,
    ) {}

    /**
     * Show the quizzes tab for a collection.
     */
    public function index(int $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        return Inertia::render('Collections/Quizzes', [
            'collection' => $this->collections->serializeCollection($collection),
            'quizzes' => $this->collections->quizzesForCollection($collection),
        ]);
    }

    /**
     * Show a single quiz for taking.
     */
    public function show(int $id, int $quizId): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $quiz = $this->collections->findOwnedQuiz($quizId, $user);

        if ($quiz === null || $quiz->getCollectionId() !== $collection->getId()) {
            return Resolver::resolveRedirector()->to('/app');
        }

        return Inertia::render('Collections/QuizShow', [
            'collection' => $this->collections->serializeCollection($collection),
            ...$this->collections->quizDetail($quiz),
        ]);
    }

    /**
     * Store a quiz attempt (calculate score, update quiz status).
     */
    public function attempt(Request $request, int $id, int $quizId): RedirectResponse
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $quiz = $this->collections->findOwnedQuiz($quizId, $user);

        if ($quiz === null || $quiz->getCollectionId() !== $collection->getId()) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $validated = $this->validateRequest($request, [
            'answers' => 'required|array',
        ]);

        $answers = $validated->assertArray('answers');
        $questions = $quiz->questions()->orderBy('order')->get();

        $correct = 0;
        $mistakes = [];
        $normalizedAnswers = [];

        foreach ($questions as $question) {
            $questionId = (string) $question->getId();
            $userAnswer = $answers[$questionId] ?? null;
            $userAnswerString = \is_string($userAnswer) ? $userAnswer : (\is_array($userAnswer) ? \implode(' ', \array_map(static fn(mixed $v): string => \is_string($v) ? $v : '', $userAnswer)) : '');
            $normalizedAnswers[$questionId] = $userAnswerString;

            if ($userAnswerString === $question->getCorrectAnswer()) {
                ++$correct;
            } else {
                $mistakes[] = [
                    'question_id' => $question->getId(),
                    'question' => $question->getQuestion(),
                    'user_answer' => $userAnswerString,
                    'correct_answer' => $question->getCorrectAnswer(),
                    'explanation' => $question->getExplanation(),
                ];
            }
        }

        $total = $questions->count();
        $score = $total > 0 ? (int) \round(($correct / $total) * 100) : 0;

        QuizAttempt::create([
            'user_id' => $user->getKey(),
            'quiz_id' => $quiz->getId(),
            'score' => $score,
            'answers' => $normalizedAnswers,
            'mistakes' => $mistakes,
            'completed_at' => Carbon::now(),
        ]);

        $quiz->update([
            'status' => QuizStatusEnum::Completed->value,
            'score' => $score,
            'completed_at' => Carbon::now(),
        ]);

        Inertia::flash('success', \__('quizzes.completed'));

        return Resolver::resolveRedirector()->back();
    }
}
