<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from the agent run stream', function (): void {
    $response = $this->get('/agent/runs/stream?run_id=any');

    $response->assertRedirect('/login');
});

\test('user cannot stream another users agent run', function (): void {
    Queue::fake();

    $owner = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $other = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $start = $this->be($owner, 'users')->postJson('/agent/runs', [
        'prompt' => 'Mine',
    ]);
    $start->assertOk();
    $runId = Typer::assertString($start->json('run_id'));

    $response = $this->be($other, 'users')->get("/agent/runs/stream?run_id={$runId}");

    $response->assertStatus(404);
});

\test('owner receives a text/event-stream response for the run', function (): void {
    Queue::fake();

    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $start = $this->be($user, 'users')->postJson('/agent/runs', [
        'prompt' => 'Stream me',
    ]);
    $start->assertOk();
    $runId = Typer::assertString($start->json('run_id'));

    $response = $this->be($user, 'users')->get("/agent/runs/stream?run_id={$runId}");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
});
