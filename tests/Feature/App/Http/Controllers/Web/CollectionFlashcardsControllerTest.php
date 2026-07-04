<?php

declare(strict_types=1);

use App\Models\Collection;
use App\Models\Flashcard;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\FlashcardFactory;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from flashcards to login', function (): void {
    $response = $this->get('/collections/1/flashcards');

    $response->assertRedirect('/login');
});

\test('authenticated user can view flashcards tab', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $response = $this->be($user, 'users')->get('/collections/' . $collection->getId() . '/flashcards', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Collections/Flashcards');
});

\test('user can review a flashcard', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $flashcard = Typer::assertInstance(FlashcardFactory::new()->for($user)->for($collection)->createOne(['review_count' => 0]), Flashcard::class);

    $this->be($user, 'users')
        ->post('/collections/' . $collection->getId() . '/flashcards/' . $flashcard->getId() . '/review', [
            'difficulty' => 'easy',
        ], $this->inertiaHeaders())
        ->assertRedirect();

    $updated = Flashcard::query()->where('id', $flashcard->getId())->first();
    static::assertInstanceOf(Flashcard::class, $updated);
    static::assertSame('easy', $updated->getDifficulty()->value);
    static::assertSame(1, $updated->getReviewCount());
});

\test('user cannot review another users flashcard', function (): void {
    $owner = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $other = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($owner)->createOne(), Collection::class);
    $flashcard = Typer::assertInstance(FlashcardFactory::new()->for($owner)->for($collection)->createOne(), Flashcard::class);

    $this->be($other, 'users')
        ->post('/collections/' . $collection->getId() . '/flashcards/' . $flashcard->getId() . '/review', [
            'difficulty' => 'easy',
        ], $this->inertiaHeaders())
        ->assertRedirect('/app');
});
