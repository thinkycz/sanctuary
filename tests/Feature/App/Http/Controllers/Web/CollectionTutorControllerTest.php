<?php

declare(strict_types=1);

use App\Ai\TutorService;
use App\Models\Collection;
use App\Models\TutorMessage;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from tutor to login', function (): void {
    $response = $this->get('/collections/1/tutor');

    $response->assertRedirect('/login');
});

\test('authenticated user can view tutor tab', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $response = $this->be($user, 'users')->get('/collections/' . $collection->getId() . '/tutor', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Collections/Tutor');
});

\test('user can send a tutor message', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $this->mock(TutorService::class, static function ($mock) use ($user, $collection): void {
        $mock->shouldReceive('sendCollectionMessage')
            ->once()
            ->andReturn(TutorMessage::create([
                'user_id' => $user->getKey(),
                'collection_id' => $collection->getId(),
                'lesson_id' => null,
                'role' => 'assistant',
                'content' => 'Mocked response.',
            ]));
    });

    $this->be($user, 'users')
        ->post('/collections/' . $collection->getId() . '/tutor', [
            'content' => 'What is a limit?',
        ], $this->inertiaHeaders())
        ->assertRedirect();
});
