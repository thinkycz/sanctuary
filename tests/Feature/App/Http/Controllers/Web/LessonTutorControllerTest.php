<?php

declare(strict_types=1);

use App\Models\Collection;
use App\Models\Lesson;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\LessonFactory;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from lesson tutor to login', function (): void {
    $response = $this->post('/lessons/1/tutor');

    $response->assertRedirect('/login');
});

\test('authenticated user can send a lesson tutor message', function (): void {
    Queue::fake();

    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $lesson = Typer::assertInstance(LessonFactory::new()->for($collection)->for($user)->createOne(), Lesson::class);

    $response = $this->be($user, 'users')->post('/lessons/' . $lesson->getId() . '/tutor', [
        'content' => 'Can you explain this grammar point?',
    ], $this->inertiaHeaders());

    $response->assertRedirect();
});
