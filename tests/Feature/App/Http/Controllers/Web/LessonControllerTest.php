<?php

declare(strict_types=1);

use App\Enums\LessonStatusEnum;
use App\Jobs\GenerateLessonJob;
use App\Models\Collection;
use App\Models\Lesson;
use App\Models\User;
use Database\Factories\CollectionFactory;
use Database\Factories\LessonFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Thinkycz\LaravelCore\Support\Typer;

\test('guest is redirected from lesson show to login', function (): void {
    $response = $this->get('/lessons/1');

    $response->assertRedirect('/login');
});

\test('authenticated user can view their lesson detail', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $lesson = Typer::assertInstance(LessonFactory::new()->for($collection)->for($user)->createOne(), Lesson::class);

    $response = $this->be($user, 'users')->get('/lessons/' . $lesson->getId(), $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'Lessons/Show');
    $response->assertJsonPath('props.lesson.id', $lesson->getId());
});

\test('user is redirected to app when lesson does not exist', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->get('/lessons/99999', $this->inertiaHeaders());

    $response->assertRedirect('/app');
});

\test('user cannot view another users lesson', function (): void {
    $owner = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $other = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($owner)->createOne(), Collection::class);
    $lesson = Typer::assertInstance(LessonFactory::new()->for($collection)->for($owner)->createOne(), Lesson::class);

    $this->be($other, 'users')
        ->get('/lessons/' . $lesson->getId(), $this->inertiaHeaders())
        ->assertRedirect('/app');
});

\test('user can delete their lesson', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $lesson = Typer::assertInstance(LessonFactory::new()->for($collection)->for($user)->createOne(), Lesson::class);

    $this->be($user, 'users')
        ->delete('/lessons/' . $lesson->getId(), [], $this->inertiaHeaders())
        ->assertRedirect('/collections/' . $collection->getId() . '/lessons');

    static::assertNull(Lesson::find($lesson->getId()));
});

\test('user can regenerate a failed lesson', function (): void {
    Queue::fake();

    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $lesson = Typer::assertInstance(LessonFactory::new()->for($collection)->for($user)->createOne(['status' => LessonStatusEnum::Failed->value]), Lesson::class);

    $this->be($user, 'users')
        ->post('/lessons/' . $lesson->getId() . '/regenerate', [], $this->inertiaHeaders())
        ->assertRedirect('/lessons/' . $lesson->getId());

    Queue::assertPushed(GenerateLessonJob::class);

    $updated = Lesson::query()->where('id', $lesson->getId())->first();
    static::assertInstanceOf(Lesson::class, $updated);
    static::assertSame(LessonStatusEnum::Pending->value, $updated->getStatus()->value);
});

\test('user can update lesson progress status', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $collection = Typer::assertInstance(CollectionFactory::new()->for($user)->createOne(), Collection::class);
    $lesson = Typer::assertInstance(LessonFactory::new()->for($collection)->for($user)->createOne(['progress_status' => 'new']), Lesson::class);

    $this->be($user, 'users')
        ->put('/lessons/' . $lesson->getId(), [
            'progress_status' => 'mastered',
        ], $this->inertiaHeaders())
        ->assertRedirect();

    $updated = Lesson::query()->where('id', $lesson->getId())->first();
    static::assertInstanceOf(Lesson::class, $updated);
    static::assertSame('mastered', $updated->getProgressStatus()->value);
});
