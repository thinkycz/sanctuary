<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Enums\TermDifficultyEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;
use Thinkycz\LaravelCore\Support\Typer;

class CollectionTermsController
{
    use ValidatesWebRequests;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly CollectionRepository $collections,
    ) {}

    /**
     * Show the terms tab for a collection.
     */
    public function index(Request $request, int $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $search = $request->query('search');
        $difficulty = $request->query('difficulty');

        return Inertia::render('Collections/Terms', [
            'collection' => $this->collections->serializeCollection($collection),
            'terms' => $this->collections->termsForCollection(
                $collection,
                \is_string($search) ? $search : null,
                \is_string($difficulty) ? $difficulty : null,
            ),
            'filters' => [
                'search' => \is_string($search) ? $search : '',
                'difficulty' => \is_string($difficulty) ? $difficulty : '',
            ],
        ]);
    }

    /**
     * Update a term's difficulty.
     */
    public function update(Request $request, int $id, int $itemId): RedirectResponse
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $item = $this->collections->findOwnedTerm($itemId, $user);

        if ($item === null || $item->getCollectionId() !== $collection->getId()) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $validated = $this->validateRequest($request, [
            'difficulty' => 'required|string|in:' . \implode(',', TermDifficultyEnum::values()),
        ]);

        $difficulty = $validated->assertString('difficulty');

        $enum = TermDifficultyEnum::tryFrom($difficulty);

        if ($enum === null) {
            Thrower::default()->message('difficulty', Typer::assertString(\__('terms.invalid_difficulty')))->throw();
        }

        $item->update([
            'difficulty' => $difficulty,
            'last_reviewed_at' => \now(),
        ]);

        Inertia::flash('success', \__('terms.updated'));

        return Resolver::resolveRedirector()->back();
    }
}
