<?php

declare(strict_types=1);

use App\Models\Collection;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from progress to login', function (): void {
    $response = $this->get('/collections/1/progress');

    $response->assertRedirect('/login');
});

\test('authenticated user can view progress tab', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);

    $response = $this->be($user, 'users')->get('/collections/' . $collection->getId() . '/progress', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Collections/Progress');
});
