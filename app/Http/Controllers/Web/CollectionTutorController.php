<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Ai\TutorService;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;
use Thinkycz\LaravelCore\Support\Typer;

class CollectionTutorController
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
     * Show the AI tutor tab for a collection.
     */
    public function index(int $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        return Inertia::render('Collections/Tutor', [
            'collection' => $this->collections->serializeCollection($collection),
            'messages' => $this->collections->tutorMessagesForCollection($collection),
        ]);
    }

    /**
     * Send a message to the collection-level AI tutor.
     */
    public function store(Request $request, int $id): RedirectResponse
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

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

        $this->tutor->sendCollectionMessage($collection, $user, $content);

        return Resolver::resolveRedirector()->back();
    }
}
