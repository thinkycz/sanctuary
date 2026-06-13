<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Web;

use App\Ai\Agents\ChatAgent;
use App\Models\User;
use Database\Factories\UserFactory;
use Laravel\Ai\Models\Conversation;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected to login from conversation endpoints', function (): void {
    $response = $this->get('/conversations/some-uuid');
    $response->assertRedirect('/login');

    $response = $this->post('/conversations', ['message' => 'hello']);
    $response->assertRedirect('/login');
});

\test('user can create a conversation and post first message', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    // Fake the ChatAgent responses (first is chat reply, second is generated title)
    ChatAgent::fake([
        'Hello, how can I help you?',
        'My Friendly Title',
    ]);

    $response = $this->be($user, 'users')
        ->post('/conversations', ['message' => 'hello']);

    // Execute the streamed content to trigger the title generation callback
    \ob_start();
    $response->sendContent();
    \ob_end_clean();

    // Should create the conversation and update its title
    $conversation = Conversation::where('user_id', $user->id)->first();
    \expect($conversation)->not->toBeNull();
    \expect($conversation->title)->toBe('My Friendly Title');

    $response->assertStatus(200);
    $response->assertHeader('X-Conversation-ID', $conversation->id);
});

\test('user can append a message to an existing conversation', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $conversation = Conversation::create([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'user_id' => $user->id,
        'title' => 'Test Conversation',
    ]);

    // Fake the ChatAgent response
    ChatAgent::fake(['Response to appended message']);

    $response = $this->be($user, 'users')
        ->post("/conversations/{$conversation->id}/messages", ['message' => 'hello again']);

    $response->assertStatus(200);
});

\test('user can view conversation details', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $conversation = Conversation::create([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'user_id' => $user->id,
        'title' => 'Test Conversation',
    ]);

    $response = $this->be($user, 'users')
        ->get("/conversations/{$conversation->id}", $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Dashboard');
    $response->assertJsonPath('props.conversation.id', $conversation->id);
    $response->assertJsonPath('props.conversation.title', 'Test Conversation');
});

\test('user is redirected to dashboard when deleting the active conversation', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $conversation = Conversation::create([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'user_id' => $user->id,
        'title' => 'Test Conversation',
    ]);

    $response = $this->be($user, 'users')
        ->from("/conversations/{$conversation->id}")
        ->delete("/conversations/{$conversation->id}");

    $response->assertRedirect('/dashboard');
    \expect(Conversation::find($conversation->id))->toBeNull();
});

\test('user is redirected back when deleting a non-active conversation', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $conversation = Conversation::create([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'user_id' => $user->id,
        'title' => 'Test Conversation',
    ]);

    $response = $this->be($user, 'users')
        ->from('/conversations/some-other-active-uuid')
        ->delete("/conversations/{$conversation->id}");

    $response->assertRedirect('/conversations/some-other-active-uuid');
    \expect(Conversation::find($conversation->id))->toBeNull();
});

\test('conversation creation is rate limited after max attempts', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    // Fake the ChatAgent response
    ChatAgent::fake(['Response']);

    for ($i = 0; $i < 30; ++$i) {
        $response = $this->be($user, 'users')
            ->post('/conversations', ['message' => 'hello']);
        $response->assertStatus(200);
    }

    $response = $this->be($user, 'users')
        ->post('/conversations', ['message' => 'hello']);
    $response->assertTooManyRequests();
});
