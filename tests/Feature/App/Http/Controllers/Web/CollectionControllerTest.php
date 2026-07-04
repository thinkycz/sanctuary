<?php

declare(strict_types=1);

use App\Models\Collection;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from collection show to login', function (): void {
    $response = $this->get('/collections/1');

    $response->assertRedirect('/login');
});

\test('authenticated user can view their collection overview', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $response = $this->be($user, 'users')->get('/collections/' . $collection->getId(), $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Collections/Show');
    $response->assertJsonPath('props.collection.id', $collection->getId());
});

\test('user is redirected to app when collection does not exist', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->get('/collections/99999', $this->inertiaHeaders());

    $response->assertRedirect('/app');
});

\test('user cannot view another users collection', function (): void {
    $owner = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $other = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($owner)->createOne(), Collection::class);

    $this->be($other, 'users')
        ->get('/collections/' . $collection->getId(), $this->inertiaHeaders())
        ->assertRedirect('/app');
});

\test('user can create a collection', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->post('/collections', [
        'title' => 'My Calculus Collection',
        'description' => 'Learning Calculus',
        'subject' => 'Mathematics',
    ], $this->inertiaHeaders());

    $collection = Collection::query()->where('user_id', $user->getKey())->first();
    static::assertInstanceOf(Collection::class, $collection);

    $response->assertRedirect('/collections/' . $collection->getId());
});

\test('user can delete their collection', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $this->be($user, 'users')
        ->delete('/collections/' . $collection->getId(), [], $this->inertiaHeaders())
        ->assertRedirect('/app');

    static::assertNull(Collection::find($collection->getId()));
});
