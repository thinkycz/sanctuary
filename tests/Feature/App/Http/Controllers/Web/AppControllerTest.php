<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from app to login', function (): void {
    $response = $this->get('/app');

    $response->assertRedirect('/login');
});

\test('authenticated user can view app empty state', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->get('/app', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'App/EmptyState');
});
