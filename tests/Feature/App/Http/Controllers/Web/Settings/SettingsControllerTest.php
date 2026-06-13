<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

\test('authenticated user can view settings page', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->get('/settings', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'settings/Index');
});

\test('guest is redirected from settings to login', function (): void {
    $response = $this->get('/settings');

    $response->assertRedirect('/login');
});

\test('user can update profile email and locale', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->post('/settings/profile', [
        'email' => 'new-email@example.com',
        'locale' => 'cs',
    ], $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'settings/Index');
    \assertInertiaFlash($response, 'success', \__('Profile updated.'));

    $user->refresh();
    static::assertSame('new-email@example.com', $user->getEmail());
    static::assertSame('cs', $user->getLocale());
});

\test('profile update rejects invalid email', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $originalEmail = $user->getEmail();

    $response = $this->be($user, 'users')->post('/settings/profile', [
        'email' => 'not-an-email',
        'locale' => 'en',
    ]);

    $response->assertStatus(422);

    $user->refresh();
    static::assertSame($originalEmail, $user->getEmail());
});

\test('profile update rejects email already in use', function (): void {
    $existing = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'taken@example.com',
    ]), User::class);

    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->post('/settings/profile', [
        'email' => $existing->getEmail(),
        'locale' => 'en',
    ]);

    $response->assertStatus(422);
});

\test('user can update their password', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $originalHash = $user->getAuthPassword();

    $response = $this->be($user, 'users')->post('/settings/password', [
        'password' => UserFactory::$password,
        'new_password' => 'new-password-123',
    ], $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'settings/Index');
    \assertInertiaFlash($response, 'success', \__('Password updated.'));

    $user->refresh();
    static::assertNotSame($originalHash, $user->getAuthPassword());
    static::assertTrue(Resolver::resolveHasher()->check('new-password-123', $user->getAuthPassword()));
});

\test('password update rejects wrong current password', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $originalHash = $user->getAuthPassword();

    $response = $this->be($user, 'users')->post('/settings/password', [
        'password' => 'not-the-current-password',
        'new_password' => 'new-password-123',
    ]);

    $response->assertStatus(422);

    $user->refresh();
    static::assertSame($originalHash, $user->getAuthPassword());
});
