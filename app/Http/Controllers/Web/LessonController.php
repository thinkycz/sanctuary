<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Enums\LessonProgressStatusEnum;
use App\Enums\LessonStatusEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Jobs\GenerateLessonJob;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;
use Thinkycz\LaravelCore\Support\Typer;

class LessonController
{
    use ValidatesWebRequests;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly CollectionRepository $collections,
    ) {}

    /**
     * Show a lesson detail page.
     */
    public function show(int $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $lesson = $this->collections->findOwnedLesson($id, $user);

        if ($lesson === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        return Inertia::render('Lessons/Show', $this->collections->lessonDetail($lesson));
    }

    /**
     * Update a lesson (e.g. mark progress status).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::mustAuth();

        $lesson = $this->collections->findOwnedLesson($id, $user);

        if ($lesson === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $validated = $this->validateRequest($request, [
            'progress_status' => 'nullable|string|in:' . \implode(',', LessonProgressStatusEnum::values()),
        ]);

        $progressStatus = $validated->assertNullableString('progress_status');

        if ($progressStatus === null) {
            Thrower::default()->message('progress_status', Typer::assertString(\__('lessons.nothing_to_update')))->throw();
        }

        $lesson->update([
            'progress_status' => $progressStatus,
        ]);

        Inertia::flash('success', \__('lessons.updated'));

        return Resolver::resolveRedirector()->back();
    }

    /**
     * Delete a lesson.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::mustAuth();

        $lesson = $this->collections->findOwnedLesson($id, $user);

        if ($lesson === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $collectionId = $lesson->getCollectionId();

        $lesson->delete();

        Inertia::flash('success', \__('lessons.deleted'));

        return Resolver::resolveRedirector()->to('/collections/' . $collectionId . '/lessons');
    }

    /**
     * Regenerate a failed or completed lesson.
     */
    public function regenerate(int $id): RedirectResponse
    {
        $user = User::mustAuth();

        $lesson = $this->collections->findOwnedLesson($id, $user);

        if ($lesson === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        if ($lesson->isGenerating()) {
            return Resolver::resolveRedirector()->to('/lessons/' . $lesson->getId());
        }

        $lesson->forceFill([
            'status' => LessonStatusEnum::Pending->value,
            'error_message' => null,
        ])->save();

        Resolver::resolveQueueingDispatcher()->dispatch(new GenerateLessonJob($lesson->getId()));

        Inertia::flash('success', \__('lessons.regeneration_started'));

        return Resolver::resolveRedirector()->to('/lessons/' . $lesson->getId());
    }
}
