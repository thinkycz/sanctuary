<?php

declare(strict_types=1);

use App\Models\Collection;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\QuizFactory;
use Database\Factories\QuizQuestionFactory;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from quizzes to login', function (): void {
    $response = $this->get('/collections/1/quizzes');

    $response->assertRedirect('/login');
});

\test('authenticated user can view quizzes tab', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $response = $this->be($user, 'users')->get('/collections/' . $collection->getId() . '/quizzes', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Collections/Quizzes');
});

\test('user can submit a quiz attempt', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $quiz = Typer::assertInstance(QuizFactory::new()->for($user)->for($collection)->createOne(['total_questions' => 2]), Quiz::class);

    $q1 = Typer::assertInstance(QuizQuestionFactory::new()->for($quiz)->createOne(['correct_answer' => 'Paris', 'order' => 0]), QuizQuestion::class);
    $q2 = Typer::assertInstance(QuizQuestionFactory::new()->for($quiz)->createOne(['correct_answer' => '4', 'order' => 1]), QuizQuestion::class);

    $this->be($user, 'users')
        ->post('/collections/' . $collection->getId() . '/quizzes/' . $quiz->getId() . '/attempt', [
            'answers' => [
                (string) $q1->getId() => 'Paris',
                (string) $q2->getId() => '4',
            ],
        ], $this->inertiaHeaders())
        ->assertRedirect();

    $attempt = QuizAttempt::query()->where('quiz_id', $quiz->getId())->first();
    static::assertInstanceOf(QuizAttempt::class, $attempt);
    static::assertSame(100, $attempt->getScore());

    $updatedQuiz = Quiz::query()->where('id', $quiz->getId())->first();
    static::assertInstanceOf(Quiz::class, $updatedQuiz);
    static::assertSame('completed', $updatedQuiz->getStatus()->value);
    static::assertSame(100, $updatedQuiz->getScore());
});

\test('quiz attempt scores partial answers correctly', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $quiz = Typer::assertInstance(QuizFactory::new()->for($user)->for($collection)->createOne(['total_questions' => 2]), Quiz::class);

    $q1 = Typer::assertInstance(QuizQuestionFactory::new()->for($quiz)->createOne(['correct_answer' => 'Paris', 'order' => 0]), QuizQuestion::class);
    $q2 = Typer::assertInstance(QuizQuestionFactory::new()->for($quiz)->createOne(['correct_answer' => '4', 'order' => 1]), QuizQuestion::class);

    $this->be($user, 'users')
        ->post('/collections/' . $collection->getId() . '/quizzes/' . $quiz->getId() . '/attempt', [
            'answers' => [
                (string) $q1->getId() => 'Paris',
                (string) $q2->getId() => 'wrong',
            ],
        ], $this->inertiaHeaders())
        ->assertRedirect();

    $attempt = QuizAttempt::query()->where('quiz_id', $quiz->getId())->first();
    static::assertInstanceOf(QuizAttempt::class, $attempt);
    static::assertSame(50, $attempt->getScore());
});
