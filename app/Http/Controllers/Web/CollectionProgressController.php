<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;

class CollectionProgressController
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly CollectionRepository $collections,
    ) {}

    /**
     * Show the progress tab for a collection.
     */
    public function __invoke(int $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        return Inertia::render('Collections/Progress', $this->collections->progressForCollection($collection));
    }
}
