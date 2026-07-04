<?php

declare(strict_types=1);

use App\Models\Collection;
use App\Models\Lesson;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from collection lessons to login', function (): void {
    $response = $this->get('/collections/1/lessons');

    $response->assertRedirect('/login');
});

\test('authenticated user can view lessons list', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $response = $this->be($user, 'users')->get('/collections/' . $collection->getId() . '/lessons', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Collections/Lessons');
});

\test('user can create a lesson', function (): void {
    Queue::fake();

    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $response = $this->be($user, 'users')->post('/collections/' . $collection->getId() . '/lessons', [
        'source_text' => 'This is a test lesson content for learning.',
        'difficulty' => 'beginner',
    ], $this->inertiaHeaders());

    $lesson = Lesson::query()->where('collection_id', $collection->getId())->first();
    static::assertInstanceOf(Lesson::class, $lesson);

    $response->assertRedirect('/lessons/' . $lesson->getId());
});
