<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Ai\TutorService;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;
use Thinkycz\LaravelCore\Support\Typer;

class LessonTutorController
{
    use ValidatesWebRequests;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly CollectionRepository $collections,
        private readonly TutorService $tutor,
    ) {}

    /**
     * Send a message to the lesson-level AI tutor.
     */
    public function store(Request $request, int $id): RedirectResponse
    {
        $user = User::mustAuth();

        $lesson = $this->collections->findOwnedLesson($id, $user);

        if ($lesson === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $collection = $this->collections->findOwnedCollection($lesson->getCollectionId(), $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $validated = $this->validateRequest($request, [
            'content' => 'required|string|min:1|max:4000',
        ]);

        $content = $validated->assertString('content');

        if (\mb_trim($content) === '') {
            Thrower::default()->message('content', Typer::assertString(\__('tutor.message_required')))->throw();
        }

        $this->tutor->sendLessonMessage($collection, $lesson, $user, $content);

        return Resolver::resolveRedirector()->back();
    }
}
