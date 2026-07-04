<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from dashboard to login', function (): void {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

\test('authenticated user is redirected from dashboard to app', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->get('/dashboard', $this->inertiaHeaders());

    $response->assertRedirect('/app');
});
