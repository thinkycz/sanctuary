<?php

declare(strict_types=1);

use App\Models\Collection;
use App\Models\Term;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\TermFactory;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from terms to login', function (): void {
    $response = $this->get('/collections/1/terms');

    $response->assertRedirect('/login');
});

\test('authenticated user can view terms tab', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $response = $this->be($user, 'users')->get('/collections/' . $collection->getId() . '/terms', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Collections/Terms');
});

\test('user can update term difficulty', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $term = Typer::assertInstance(TermFactory::new()->for($user)->for($collection)->createOne(['difficulty' => 'unknown']), Term::class);

    $this->be($user, 'users')
        ->put('/collections/' . $collection->getId() . '/terms/' . $term->getId(), [
            'difficulty' => 'mastered',
        ], $this->inertiaHeaders())
        ->assertRedirect();

    $updated = Term::query()->where('id', $term->getId())->first();
    static::assertInstanceOf(Term::class, $updated);
    static::assertSame('mastered', $updated->getDifficulty()->value);
});

\test('user cannot update another users term', function (): void {
    $owner = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $other = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($owner)->createOne(), Collection::class);
    $term = Typer::assertInstance(TermFactory::new()->for($owner)->for($collection)->createOne(), Term::class);

    $this->be($other, 'users')
        ->put('/collections/' . $collection->getId() . '/terms/' . $term->getId(), [
            'difficulty' => 'mastered',
        ], $this->inertiaHeaders())
        ->assertRedirect('/app');
});
