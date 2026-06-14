<?php

declare(strict_types=1);

use App\Models\AgentRun;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Thinkycz\LaravelCore\Support\Typer;

\test('authenticated user can cancel a queued or running agent run', function (): void {
    Queue::fake();

    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $start = $this->be($user, 'users')->postJson('/agent/runs', [
        'prompt' => 'Cancel me',
    ]);
    $start->assertOk();
    $runId = Typer::assertString($start->json('run_id'));

    $cancel = $this->be($user, 'users')->postJson('/agent/runs/cancel', [
        'run_id' => $runId,
    ]);

    $cancel->assertOk();
    $cancel->assertJsonPath('status', AgentRun::STATUS_CANCELLED);

    static::assertSame(AgentRun::STATUS_CANCELLED, AgentRun::query()->where('id', $runId)->value('status'));
});

\test('user cannot cancel another users agent run', function (): void {
    Queue::fake();

    $owner = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $other = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $start = $this->be($owner, 'users')->postJson('/agent/runs', [
        'prompt' => 'Mine',
    ]);
    $start->assertOk();
    $runId = Typer::assertString($start->json('run_id'));

    $otherCancel = $this->be($other, 'users')->postJson('/agent/runs/cancel', [
        'run_id' => $runId,
    ]);

    $otherCancel->assertStatus(404);
});
